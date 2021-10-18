package com.intervan.jasper.general;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Map;

import net.sf.jasperreports.engine.JRDefaultScriptlet;
import net.sf.jasperreports.engine.JRParameter;
import net.sf.jasperreports.engine.JRScriptletException;
import net.sf.jasperreports.engine.JasperReport;

/**
 * Esta clase base de scriptlets tiene como funcionalidad configurar elementos
 * antes de que sean usados por el resto de los scriptlets, como por ejemplo
 * recuperar el nombre del reporte, o instanciar la clase propia de conexión con
 * la DB.
 * 
 * Se encarga de imprimir por consola si el flag DEBUG_QUERIES está en
 * verdadero.
 * 
 * @author lgraziani
 * @version 1.0.0
 */
public class BaseScriptlet extends JRDefaultScriptlet {
	protected JasperConnection connection;
	protected String reportName;
	protected String scriptletName;
	protected String mainScriptletName;
	private final static Logger logger = new Logger(BaseScriptlet.class);

	@Override
	public void beforeReportInit() throws JRScriptletException {
		JasperReport report = (JasperReport) this.getParameterValue(JRParameter.JASPER_REPORT);

		this.mainScriptletName = report.getScriptletClass();
		this.mainScriptletName = this.mainScriptletName == null ? "" : this.mainScriptletName;
		this.scriptletName = this.getClass().getSimpleName();
		this.reportName = report.getName().toUpperCase();

		Map<String, Boolean> flags = this.dbDebugFlags();

		if (flags.get("DEBUG_QUERIES") && this.mainScriptletName.contains(this.scriptletName)) {
			try {
				String mainQuery = report.getQuery().getText().replaceAll("[\\t\\n\\r]+", " ");
				
				logger.debug(reportName + " (Scriplet: " + this.scriptletName + ") - MAIN QUERY: " + mainQuery);
			} catch (NullPointerException err) {
				// Ignorar. Significa que el reporte no tiene query
			}
		}

		this.connection = new JasperConnection(
				(Connection) this.getParameterValue(JRParameter.REPORT_CONNECTION), this.reportName,
				this.scriptletName, flags);

		if (flags.get("DEBUG_QUERIES") && this.mainScriptletName.contains(this.scriptletName)) {
			this.printOpenedCursors();
		}
	}

	@Override
	public void afterDetailEval() throws JRScriptletException {
		if (!this.dbDebugFlags().get("DEBUG_QUERIES") || !this.mainScriptletName.contains(this.scriptletName)) {
			return;
		}

//		this.printOpenedCursors();
	}

	private void printOpenedCursors() throws JRScriptletException {
		this.connection.query("SELECT * FROM (SELECT ss.sid, ss.value FROM v$sesstat ss, v$statname sn "
				+ "WHERE ss.statistic# = sn.statistic# AND sn.name like '%opened cursors current%' "
				+ "ORDER BY value desc, sid desc) WHERE value > 0", new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
				ResultSet rs = stmt.executeQuery();

				logger.debug(reportName + " (Scriplet: " + scriptletName + ") - Listado de cursores: ");

				while (rs.next()) {
					String sid = rs.getString("sid");
					String value = rs.getString("value");

					logger.cacheInline("[SID:" + sid + ", VAL:" + value + "]");
				}

				logger.flushInline(Logger.Methods.debug);

				return rs;
			}
		});

	}

	/**
	 * Recupera los flags para debug. Por defecto se setean en falso.
	 * 
	 * @return Mapa de flag -> valor
	 */
	private Map<String, Boolean> dbDebugFlags() {
		Map<String, Boolean> debugFlags = new HashMap<String, Boolean>();

		try {
			Object DEBUG_QUERIES = this.getParameterValue("DEBUG_QUERIES");

			DEBUG_QUERIES = DEBUG_QUERIES instanceof Boolean ? DEBUG_QUERIES : "S"
					.equalsIgnoreCase((String) DEBUG_QUERIES);
			debugFlags.put("DEBUG_QUERIES", (Boolean) DEBUG_QUERIES);
		} catch (JRScriptletException e) {
			debugFlags.put("DEBUG_QUERIES", false);

			logger.debug(this.reportName + " (Scriplet: " + this.scriptletName
					+ ") - DEBUG_QUERIES: no configurado");
		}

		return debugFlags;
	}
}
