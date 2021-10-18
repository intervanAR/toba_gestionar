package com.intervan.jasper.rentas;

import java.io.IOException;
import java.sql.Clob;
import java.sql.SQLException;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.StringFunctionalities;

public class LibreDeuda extends BaseScriptlet {
	public String parseClob(Clob clob) throws JRScriptletException {
		try {
			return StringFunctionalities.parseClob(clob);
		} catch (IOException e) {
			throw new JRScriptletException(e);
		} catch (SQLException e) {
			throw new JRScriptletException(e);
		}
	}
}
