package com.intervan.jasper.rentas;

import java.sql.CallableStatement;
import java.sql.SQLException;
import java.sql.Types;
import java.text.SimpleDateFormat;
import java.util.Date;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.JasperOperation;
import com.intervan.jasper.general.StringFunctionalities;

public class LiquidacionDeuda extends BaseScriptlet {
	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		String cadenaDeuda = (String) this.getParameterValue("p_cadena_deuda");

		// La tabla temporal que se utiliza requiere que se desactive el auto
		// commit por operación, porque sino se pierden los datos
		this.connection.disableAutoCommit();

		this.generarInforme(cadenaDeuda);
		this.estadoExpediente(cadenaDeuda);
	}

	private void estadoExpediente(final String cadenaDeuda) throws JRScriptletException {
		String query = "{ call pkg_fiscalia.estado_expediente(?) }";

		this.connection.callProcedure(query, new JasperOperation() {
			@Override
			public void callProcedure(CallableStatement callableStmt) throws JRScriptletException,
					SQLException {
				callableStmt.setString(1, cadenaDeuda);
				callableStmt.execute();
			}
		});
	}

	private void generarInforme(final String cadenaDeuda) throws JRScriptletException {
		final Integer cuentaId = Integer.parseInt((String) this.getParameterValue("p_id_cuenta"));
		final String tipoInforme = (String) this.getParameterValue("p_tipo_informe");
		final String fechaActualizacion = StringFunctionalities.empty((String) this
				.getParameterValue("p_fecha_actualizacion")) ? new SimpleDateFormat("dd/MM/yyyy")
				.format(new Date()) : (String) this.getParameterValue("p_fecha_actualizacion");
		String query = "{ call ? := pkg_actualizaciones.genera_informe(?, TO_DATE(?, 'DD/MM/YYYY'), ?, ?) }";

		this.connection.callProcedure(query, new JasperOperation() {
			@Override
			public void callProcedure(CallableStatement callableStmt) throws JRScriptletException,
					SQLException {
				callableStmt.registerOutParameter(1, Types.VARCHAR);
				callableStmt.setInt(2, cuentaId);
				callableStmt.setString(3, fechaActualizacion);
				callableStmt.setString(4, cadenaDeuda);
				callableStmt.setString(5, tipoInforme);

				callableStmt.execute();

				String result = callableStmt.getString(1);

				if (!result.contains("OK")) {
					throw new JRScriptletException(result);
				}
			}
		});
	}
}