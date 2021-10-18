package com.intervan.jasper.rentas;

import java.sql.CallableStatement;
import java.sql.SQLException;
import java.sql.Types;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.JasperOperation;
import com.intervan.jasper.rentas.daos.VariosDao;

/**
 * @author lgraziani
 * @version 1.1.0
 */
public class ImprimeContrato extends BaseScriptlet {
	private String ordenanzaContratoConvenio;

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		final String idComprobante = (String) this.getParameterValue("p_id_comprobante");
		String query = "{ call ? := pkg_actualizaciones.genera_informe_conv(?) }";

		// La tabla temporal que se utiliza requiere que se desactive el auto
		// commit por operación, porque sino se pierden los datos
		this.connection.disableAutoCommit();

		this.connection.callProcedure(query, new JasperOperation() {
			@Override
			public void callProcedure(CallableStatement callableStmt) throws JRScriptletException,
					SQLException {
				callableStmt.registerOutParameter(1, Types.VARCHAR);
				callableStmt.setString(2, idComprobante);

				callableStmt.execute();

				String result = callableStmt.getString(1);

				if (!result.contains("OK")) {
					throw new JRScriptletException(result);
				}
			}
		});
	}

	public String formatTipoLetra(String text) {
		String[] parts = text.split(" ");
		String camelCased = "";

		for (String part : parts) {
			camelCased += this.toProperCase(part);
		}

		return camelCased;
	}

	public String ordenanzaContratoConvenio() throws JRScriptletException {
		if (this.ordenanzaContratoConvenio != null) {
			return this.ordenanzaContratoConvenio;
		}
		this.ordenanzaContratoConvenio = VariosDao.getConfiguracion("ORDENANZA_CONTRATO_CONV",
				this.connection, null);

		return this.ordenanzaContratoConvenio;
	}

	private String toProperCase(String part) {
		return part.substring(0, 1).toUpperCase() + part.substring(1).toLowerCase();
	}

}
