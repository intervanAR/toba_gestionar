package com.intervan.jasper.general;

import java.sql.CallableStatement;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

import net.sf.jasperreports.engine.JRScriptletException;

/**
 * Anonymous class para consultas
 * 
 * @author lgraziani
 * @version 1.0.0
 */
public class JasperOperation {
	public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
		throw new JRScriptletException("La función `JasperOperation@query` debe sobreescribirse");
	}

	public void callProcedure(CallableStatement callableStmt) throws JRScriptletException, SQLException {
		throw new JRScriptletException("La función `JasperOperation@callProcedure` debe sobreescribirse");
	}
}
