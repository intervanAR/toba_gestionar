package com.intervan.jasper.rentas;

import java.sql.CallableStatement;
import java.sql.SQLException;
import java.sql.Types;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.JasperOperation;

/**
 * @author lgraziani
 * @version 1.0.0
 */
public class ResumenDeuda extends BaseScriptlet {

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		final String cadenaCuentas = (String) this.getParameterValue("p_cadena_cuentas");
		final String fechaActualizacion = (String) this.getParameterValue("p_fecha_actualizacion");
		final String soloDeuda = (String) this.getParameterValue("p_solo_deuda");
		final String fechaDesde = (String) this.getParameterValue("p_fecha_desde");
		final String fechaHasta = (String) this.getParameterValue("p_fecha_hasta");

		String query = "{ call ? := pkg_actualizaciones.genera_resumen(?, TO_DATE(?,'DD/MM/YYYY'), ?, TO_DATE(?,'DD/MM/YYYY'), TO_DATE(?,'DD/MM/YYYY')) }";

		this.connection.disableAutoCommit();
		this.connection.callProcedure(query, new JasperOperation() {
			@Override
			public void callProcedure(CallableStatement callableStmt) throws JRScriptletException,
					SQLException {
				callableStmt.registerOutParameter(1, Types.VARCHAR);
				callableStmt.setString(2, cadenaCuentas);
				callableStmt.setString(3, fechaActualizacion);
				callableStmt.setString(4, soloDeuda);
				callableStmt.setString(5, fechaDesde);
				callableStmt.setString(6, fechaHasta);

				callableStmt.execute();

				String result = callableStmt.getString(1);

				if (!result.contains("OK")) {
					throw new JRScriptletException(result);
				}
			}
		});
	}
}
