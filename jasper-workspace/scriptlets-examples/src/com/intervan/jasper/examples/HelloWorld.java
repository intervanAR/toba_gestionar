package com.intervan.jasper.examples;

import java.sql.CallableStatement;
import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.sql.Types;

import net.sf.jasperreports.engine.JRDefaultScriptlet;
import net.sf.jasperreports.engine.JRParameter;
import net.sf.jasperreports.engine.JRScriptletException;

public class HelloWorld extends JRDefaultScriptlet {

	public String printHello() {
		return "Hello World";
	}

	/**
	 * Este ejemplo realiza una consulta sencilla a la DB a partir de la
	 * conexión activa que tiene Jasper.
	 * 
	 * @return
	 * @throws JRScriptletException
	 * @throws SQLException
	 */
	public String getDetalle() throws JRScriptletException, SQLException {
		Connection con = (Connection) this
				.getParameterValue(JRParameter.REPORT_CONNECTION);
		Statement stmt = con.createStatement();
		String query = "SELECT 'Win win' as win FROM DUAL";
		String respuesta = "";

		ResultSet rs = stmt.executeQuery(query);
		while (rs.next()) {
			respuesta = rs.getString("win");
		}
		stmt.close();

		return respuesta;
	}

	/**
	 * En este ejemplo se ejecuta un procedimiento de DB.
	 * 
	 * @return
	 * @throws JRScriptletException
	 * @throws SQLException
	 */
	public String execProcedure() throws JRScriptletException, SQLException {
		Connection con = (Connection) this
				.getParameterValue(JRParameter.REPORT_CONNECTION);
		String query = "{ call ? := PKG_ACTUALIZACIONES.porcentaje_deu_susp() }";
		CallableStatement cStmt = con.prepareCall(query);

		cStmt.registerOutParameter(1, Types.INTEGER);

		cStmt.execute();

		return cStmt.getString(1);
	}
}
