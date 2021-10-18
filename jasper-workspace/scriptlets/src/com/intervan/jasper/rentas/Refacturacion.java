package com.intervan.jasper.rentas;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.StringFunctionalities;
import com.intervan.jasper.rentas.daos.VariosDao;

/**
 * Reporte de Rentas para refacturación.
 * 
 * @author lgraziani
 * @version 1.1.0
 */
public class Refacturacion extends BaseScriptlet {
	private String mostrarNroDebito;

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		this.mostrarNroDebito = VariosDao.getConfiguracion("MOSTRAR_NRO_DEBITO_REPORTES", this.connection,
				"1");
	}

	public String mostrarNroDebito() throws JRScriptletException {
		return this.mostrarNroDebito;
	}

	public String generarFiltroConjFacturas() {
		try {
			String cadenaFacturas = (String) this.getParameterValue("p_cadena_facturas");

			if (cadenaFacturas != null) {
				return "factura.id_comprobante IN ("
						+ StringFunctionalities.parseIDChainFromParamToCSV(cadenaFacturas) + ")";
			}
		} catch (JRScriptletException e1) {
			// Do nothing if not exist
		}
		try {
			String cadenaComprobantes = (String) this.getParameterValue("p_cadena_comp");

			return "factura.id_comprobante in (" + "SELECT detalles.id_comprobante "
					+ "FROM RE_DETALLES_FACTURAS detalles " + "WHERE detalles.id_comprobante_cancela IN ("
					+ StringFunctionalities.parseIDChainFromParamToCSV(cadenaComprobantes) + "))";
		} catch (JRScriptletException e) {
			return " 1 != 1";
		}
	}
}
