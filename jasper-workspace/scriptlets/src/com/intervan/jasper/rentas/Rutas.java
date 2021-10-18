package com.intervan.jasper.rentas;

import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.JasperOperation;

/**
 * Reporte de Rentas para rutas.
 * 
 * @author lgraziani
 * @version 1.0.0
 */
public class Rutas extends BaseScriptlet {
	private String rutaDescripcion;

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		String cadenaRutas = (String) this.getParameterValue("p_cadena_rutas");
		String query = "SELECT nro_ruta, descripcion ruta_desc " + "FROM re_rutas_distribucion "
				+ "WHERE INSTR('" + cadenaRutas + "', ('#' || nro_ruta || '#')) > 0 " + "ORDER BY nro_ruta";

		this.connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {
					rutaDescripcion = "Ruta: " + rs.getString("nro_ruta") + " - " + rs.getString("ruta_desc");
				}

				return rs;
			}
		});
	}

	public String rutaDescripcion() {
		return this.rutaDescripcion;
	}
}
