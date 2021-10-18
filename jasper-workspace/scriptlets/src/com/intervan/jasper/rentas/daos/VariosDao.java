package com.intervan.jasper.rentas.daos;

import java.sql.CallableStatement;
import java.sql.SQLException;
import java.sql.Types;
import java.util.HashMap;
import java.util.Map;

import com.intervan.jasper.general.JasperConnection;
import com.intervan.jasper.general.JasperOperation;
import com.intervan.jasper.general.Logger;

import net.sf.jasperreports.engine.JRScriptletException;

/**
 * Se encarga de invocar los métodos del paquete PKG_VARIOS.
 * 
 * @author lgraziani
 * @version 1.1.0
 */
public final class VariosDao {
	private static String configResult = null;
	private static Map<String, String> configuraciones = new HashMap<String, String>();
	private final static Logger logger = new Logger(VariosDao.class);

	/**
	 * Recupera una configuración de PKG_VARIOS.valor_configuraciones
	 * 
	 * @return String
	 * @throws JRScriptletException
	 * @throws SQLException
	 */
	public static String getConfiguracion(final String configuracion, final JasperConnection connection)
			throws JRScriptletException {
		if (configuraciones.containsKey(configuracion)) {
			return configuraciones.get(configuracion);
		}
		String query = "{ call ? := PKG_VARIOS.VALOR_CONFIGURACIONES(?) }";

		connection.callProcedure(query, new JasperOperation() {
			@Override
			public void callProcedure(CallableStatement callableStmt) throws JRScriptletException,
					SQLException {
				callableStmt.registerOutParameter(1, Types.VARCHAR);
				callableStmt.setString(2, configuracion);

				if (connection.flag("DEBUG_QUERIES")) {
					logger.debug(connection.reportName() + " (Scriplet: " + connection.scriptletName()
							+ ") - PARAMETERS: (1) RESPUESTA");
					logger.debug(connection.reportName() + " (Scriplet: " + connection.scriptletName()
							+ ") - PARAMETERS: (2) " + configuracion);
				}

				callableStmt.execute();

				configResult = callableStmt.getString(1);
			}
		});

		configuraciones.put(configuracion, configResult);

		return configResult;
	}

	public static String getConfiguracion(String configuracion, JasperConnection connection,
			String valorPorDefecto) throws JRScriptletException {
		try {
			return getConfiguracion(configuracion, connection);
		} catch (JRScriptletException e) {
			// Si el error significa que no existe el campo ese, se ignora
			if (!e.getMessage().toLowerCase().contains(configuracion.toLowerCase())) {
				throw new JRScriptletException(e);
			}
			return valorPorDefecto;
		}
	}
}
