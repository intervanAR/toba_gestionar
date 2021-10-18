package com.intervan.jasper.rentas;

import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.JasperOperation;

public class ImprimeFacturas extends BaseScriptlet {
	private String impuesto = null;

	public String impuestoSegunComprobante(final Integer idComprobante) throws JRScriptletException {
		if (this.impuesto != null) {
			return this.impuesto;
		}

		String query = "SELECT imp.descripcion FROM re_comprobantes_cuenta cc, re_impuestos imp "
				+ "WHERE cc.id_comprobante = ? AND imp.cod_impuesto = cc.cod_impuesto";

		this.connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
				stmt.setInt(1, idComprobante);
				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {
					impuesto = rs.getString("descripcion");
				}

				return rs;
			}
		});

		if (this.impuesto == null) {
			this.impuesto = "";
		}

		return this.impuesto;
	}
}
