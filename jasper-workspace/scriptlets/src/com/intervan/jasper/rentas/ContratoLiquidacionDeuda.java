package com.intervan.jasper.rentas;

import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Map;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.JasperOperation;

public class ContratoLiquidacionDeuda extends BaseScriptlet {
	private static Map<String, Map<String, String>> convenios = new HashMap<String, Map<String, String>>();
	private Map<String, String> convenio;

	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		final String idComprobante = (String) this.getParameterValue("p_id_comprobante");
		
		// Otra instancia alimentó el estado
		if (convenios.containsKey(idComprobante)) {
			convenio = convenios.get(idComprobante);

			return;
		}

		String query = "SELECT nro_convenio, " + "TO_CHAR(fecha_generacion, 'dd/mm/yyyy') fecha_generacion, "
				+ "TO_CHAR(fecha_primer_vto, 'dd/mm/yyyy') fecha_primer_vto, "
				+ "TO_CHAR(fecha_actualizacion, 'dd/mm/yyyy') fecha_actualizacion "
				+ "FROM re_convenios WHERE id_comprobante = ?";

		this.connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
				stmt.setString(1, idComprobante);

				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {
					convenio = new HashMap<String, String>();
					
					convenio.put("nroConvenio", rs.getString("nro_convenio"));
					convenio.put("fechaRealizacion", rs.getString("fecha_generacion"));
					convenio.put("fecha1Vto", rs.getString("fecha_primer_vto"));
					convenio.put("fechaActualizacion", rs.getString("fecha_actualizacion"));
					
					convenios.put(idComprobante, convenio);
				}

				return rs;
			}
		});
	}

	public String getNroConvenio() {
		return convenio.get("nroConvenio");
	}

	public String getFechaRealizacion() {
		return convenio.get("fechaRealizacion");
	}

	public String getFecha1Vto() {
		return convenio.get("fecha1Vto");
	}

	public String getFechaActualizacion() {
		return convenio.get("fechaActualizacion");
	}
}
