package com.intervan.jasper.general;

import java.sql.CallableStatement;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Map;

import net.sf.jasperreports.engine.JRScriptletException;

/**
 * Esta clase se encarga de realizar operaciones a la DB. En desarrollo loguea
 * información que asiste al desarrollador.
 * 
 * @author lgraziani
 * @version 1.0.0
 */
public class JasperConnection {
	private Connection connection;
	private Map<String, Boolean> flags;
	private String reportName;
	private String scriptletName;
	private final static Logger logger = new Logger(JasperConnection.class);

	public JasperConnection(Connection connection, String reportName, String scriptletName,
			Map<String, Boolean> flags) {
		this.connection = connection;
		this.flags = flags;
		this.reportName = reportName;
		this.scriptletName = scriptletName;
	}

	public String reportName() {
		return this.reportName;
	}

	public String scriptletName() {
		return this.scriptletName;
	}

	public Boolean flag(String flag) throws JRScriptletException {
		if (!this.flags.containsKey(flag)) {
			throw new JRScriptletException("El flag `" + flag + "` no existe");
		}
		return this.flags.get(flag);
	}

	public void disableAutoCommit() throws JRScriptletException {
		try {
			this.connection.setAutoCommit(false);
		} catch (SQLException e) {
			throw new JRScriptletException(e);
		}
	}

	public void callProcedure(String query, JasperOperation operation) throws JRScriptletException {
		CallableStatement callableStmt = null;

		try {
			callableStmt = this.connection.prepareCall(query);

			if (flags.get("DEBUG_QUERIES")) {
				logger.debug(this.reportName + " (Scriplet: " + this.scriptletName + ") - PROCEDURE_CALL: "
						+ query);
			}

			operation.callProcedure(callableStmt);
		} catch (SQLException e) {
			throw new JRScriptletException(e);
		} finally {
			try {
				if (callableStmt != null) {
					callableStmt.close();
				}
			} catch (SQLException e) {
				throw new JRScriptletException(e);
			}
		}
	}

	public void query(String query, JasperOperation operation) throws JRScriptletException {
		PreparedStatement stmt = null;
		ResultSet rs = null;

		try {
			stmt = this.connection.prepareStatement(query);

			if (flags.get("DEBUG_QUERIES")) {
				logger.debug(this.reportName + " (Scriplet: " + this.scriptletName + ") - QUERY: " + query);
			}

			rs = operation.query(stmt);
		} catch (SQLException e) {
			throw new JRScriptletException(e);
		} finally {
			try {
				if (rs != null) {
					rs.close();
				}
				if (stmt != null) {
					stmt.close();
				}
			} catch (SQLException e) {
				throw new JRScriptletException(e);
			}
		}
	}
}
