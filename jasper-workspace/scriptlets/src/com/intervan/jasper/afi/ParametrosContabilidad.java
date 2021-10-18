package com.intervan.jasper.afi;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;

/**
 * Arma p_parametros del header para cada reporte.
 * 
 * @author ddiluca
 */
public class ParametrosContabilidad extends BaseScriptlet {

	private UnidadAdministracion unidadAdministracion;
	private Ejercicio ejercicio;

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		String codUnidadAdministracion = null;
		String idEjercicio = null;

		// Obtengo parametros del reporte
		try {
			codUnidadAdministracion = (String) this.getParameterValue("p_unidad_administracion");
		} catch (Exception e) {
			System.out.println("El parametro p_unidad_administracion no existe");
		}

		try {
			idEjercicio = (String) this.getParameterValue("p_ejercicio");
		} catch (Exception e) {
			System.out.println("El parametro p_ejercicio no existe");
		}

		// Consultas
		if (codUnidadAdministracion != null && !(codUnidadAdministracion).isEmpty()) {
			this.unidadAdministracion = VariosAfiDao.getUnidadAdministracion(codUnidadAdministracion,
					this.connection);

		}

		if (idEjercicio != null && !(idEjercicio).isEmpty()) {
			this.ejercicio = VariosAfiDao.getEjercicio(idEjercicio, this.connection);
		}

	}

	public String getParametroRepCpBalanceGen() {
		// Parametros del reporte
		String p_fecha_desde;
		String p_fecha_hasta;
		String p_formato_libro;
		String p_cuenta_desde;
		String p_cuenta_hasta;

		try {
			p_fecha_desde = (String) this.getParameterValue("p_fecha_desde");
		} catch (JRScriptletException e) {
			p_fecha_desde = "";
		}

		try {
			p_fecha_hasta = (String) this.getParameterValue("p_fecha_hasta");
		} catch (JRScriptletException e) {
			p_fecha_hasta = "";
		}

		try {
			p_formato_libro = (String) this.getParameterValue("p_formato_libro");
			System.out.println(p_formato_libro);
		} catch (JRScriptletException e) {
			p_formato_libro = "";
		}

		try {
			p_cuenta_desde = (String) this.getParameterValue("p_cuenta_desde");
		} catch (JRScriptletException e) {
			p_cuenta_desde = "";
		}

		try {
			p_cuenta_hasta = (String) this.getParameterValue("p_cuenta_hasta");
		} catch (JRScriptletException e) {
			p_cuenta_hasta = "";
		}

		String parametro = "EJERCICIO FINANCIERO " + this.ejercicio.nroEjercicio + " DEL " + p_fecha_desde
				+ " AL " + p_fecha_hasta;
		if (p_formato_libro.equals("N")) {
			parametro += " , UA: ";
			if (this.unidadAdministracion == null) {
				parametro += "Todas ";
			} else {
				parametro += this.unidadAdministracion.descripcion;
			}
			parametro += ", Cuentas: " + p_cuenta_desde + " - " + p_cuenta_hasta;
		}
		return parametro;
	}

	public String getParametroRepCpBalanceSys() {
		// Parametros del reporte
		String p_fecha_desde;
		String p_fecha_hasta;
		String p_formato_libro;
		String p_cuenta_desde;
		String p_cuenta_hasta;

		try {
			p_fecha_desde = (String) this.getParameterValue("p_fecha_desde");
		} catch (JRScriptletException e) {
			p_fecha_desde = "";
		}

		try {
			p_fecha_hasta = (String) this.getParameterValue("p_fecha_hasta");
		} catch (JRScriptletException e) {
			p_fecha_hasta = "";
		}

		try {
			p_formato_libro = (String) this.getParameterValue("p_formato_libro");
			System.out.println(p_formato_libro);
		} catch (JRScriptletException e) {
			p_formato_libro = "";
		}

		try {
			p_cuenta_desde = (String) this.getParameterValue("p_cuenta_desde");
		} catch (JRScriptletException e) {
			p_cuenta_desde = "";
		}

		try {
			p_cuenta_hasta = (String) this.getParameterValue("p_cuenta_hasta");
		} catch (JRScriptletException e) {
			p_cuenta_hasta = "";
		}

		String parametro = "EJERCICIO FINANCIERO " + this.ejercicio.nroEjercicio + " DEL " + p_fecha_desde
				+ " AL " + p_fecha_hasta;
		if (p_formato_libro.equals("N")) {
			parametro += " , UA: ";
			if (this.unidadAdministracion == null) {
				parametro += "Todas ";
			} else {
				parametro += this.unidadAdministracion.descripcion;
			}
			parametro += ", Cuentas: " + p_cuenta_desde + " - " + p_cuenta_hasta;
		}
		return parametro;
	}

	public String getParametroRepCpDeudaCtaCont() {
		// Parametros del reporte
		String p_fecha_desde;
		String p_fecha_hasta;
		String p_formato_libro;
		String p_cuenta_desde;
		String p_cuenta_hasta;

		try {
			p_fecha_desde = (String) this.getParameterValue("p_fecha_desde");
		} catch (JRScriptletException e) {
			p_fecha_desde = "";
		}

		try {
			p_fecha_hasta = (String) this.getParameterValue("p_fecha_hasta");
		} catch (JRScriptletException e) {
			p_fecha_hasta = "";
		}

		try {
			p_formato_libro = (String) this.getParameterValue("p_formato_libro");
			System.out.println(p_formato_libro);
		} catch (JRScriptletException e) {
			p_formato_libro = "";
		}

		try {
			p_cuenta_desde = (String) this.getParameterValue("p_cuenta_desde");
		} catch (JRScriptletException e) {
			p_cuenta_desde = "";
		}

		try {
			p_cuenta_hasta = (String) this.getParameterValue("p_cuenta_hasta");
		} catch (JRScriptletException e) {
			p_cuenta_hasta = "";
		}

		String parametro = "EJERCICIO FINANCIERO " + this.ejercicio.nroEjercicio + " DEL " + p_fecha_desde
				+ " AL " + p_fecha_hasta;

		if (this.unidadAdministracion != null) {
			parametro += " " + this.unidadAdministracion.descripcion + " - ";
		}

		parametro += " Cuentas: " + p_cuenta_desde + " - " + p_cuenta_hasta;
		return parametro;
	}

	public String getParametroRepPrEjecIngDet() {
		// Parametros del reporte
		String p_fecha_desde;
		String p_fecha_hasta;
		String p_formato_libro;
		String p_recurso_desde;
		String p_recurso_hasta;
		String p_entidad_desde;
		String p_entidad_hasta;
		String p_figurativo;

		try {
			p_fecha_desde = (String) this.getParameterValue("p_fecha_desde");
		} catch (JRScriptletException e) {
			p_fecha_desde = "";
		}

		try {
			p_fecha_hasta = (String) this.getParameterValue("p_fecha_hasta");
		} catch (JRScriptletException e) {
			p_fecha_hasta = "";
		}

		try {
			p_formato_libro = (String) this.getParameterValue("p_formato_libro");
			System.out.println(p_formato_libro);
		} catch (JRScriptletException e) {
			p_formato_libro = "";
		}

		try {
			p_recurso_desde = (String) this.getParameterValue("p_recurso_desde");
		} catch (JRScriptletException e) {
			p_recurso_desde = "";
		}

		try {
			p_recurso_hasta = (String) this.getParameterValue("p_recurso_hasta");
		} catch (JRScriptletException e) {
			p_recurso_hasta = "";
		}
		try {
			p_figurativo = (String) this.getParameterValue("p_figurativo");
		} catch (JRScriptletException e) {
			p_figurativo = "";
		}
		try {
			p_entidad_desde = (String) this.getParameterValue("p_entidad_desde");
		} catch (JRScriptletException e) {
			p_entidad_desde = "";
		}

		try {
			p_entidad_hasta = (String) this.getParameterValue("p_entidad_hasta");
		} catch (JRScriptletException e) {
			p_entidad_hasta = "";
		}

		String parametro = "EJERCICIO FINANCIERO " + this.ejercicio.nroEjercicio + " DEL " + p_fecha_desde
				+ " AL " + p_fecha_hasta;

		parametro += " , UA: ";
		if (this.unidadAdministracion == null) {
			parametro += "Todas ";
		} else {
			parametro += this.unidadAdministracion.descripcion;
		}

		parametro += ", Jurisdicción: " + p_entidad_desde + " - " + p_entidad_hasta;

		parametro += ", Recurso: " + p_recurso_desde + " - " + p_recurso_hasta;

		if (p_figurativo.equals("S")) {
			parametro += ", Incluye Gastos Figurativos";
		} else {
			parametro += ", No Incluye Gastos Figurativos";
		}

		return parametro;
	}
}
