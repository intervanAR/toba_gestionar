package com.intervan.jasper.rentas.daos;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;

/**
 * Recupera todos los datos necesario del municipio una única vez y los pone
 * accesibles a través de getters.
 * 
 * @author lgraziani
 * @version 1.1.0
 */
public class MunicipioDatos extends BaseScriptlet {
	private String nombre;
	private String nombreCiudad;
	private String provinciaNombre;
	private String cuit;
	private String domicilio;
	private String telefono;
	private String telefonoConLeyenda;
	private String lugaresDePago;
	private String logo;
	private String leyenda1;
	private String leyenda2;
	private String nombreDgr;

	public String getNombre() throws JRScriptletException {
		//if (this.nombre == null) {
			this.nombre = VariosDao.getConfiguracion("NOMBRE_MUNICIPIO", this.connection);
		//}

		return this.nombre;
	}

	public String getNombreCiudad() throws JRScriptletException {
		//if (this.nombreCiudad == null) {
			this.nombreCiudad = VariosDao.getConfiguracion("CIUDAD_MUNICIPIO", this.connection);
		//}

		return this.nombreCiudad;
	}

	public String getProvinciaNombre() throws JRScriptletException {
		//if (this.provinciaNombre == null) {
			this.provinciaNombre = VariosDao.getConfiguracion("PROVINCIA_MUNICIPIO", this.connection);
		//}
		return this.provinciaNombre;
	}

	public String getCuit() throws JRScriptletException {
		//if (this.cuit == null) {
			this.cuit = VariosDao.getConfiguracion("CUIT_MUNICIPIO", this.connection);
		//}
		return this.cuit;
	}

	public String getDomicilio() throws JRScriptletException {
		//if (this.domicilio == null) {
			this.domicilio = VariosDao.getConfiguracion("DOMICILIO_MUNICIPIO", this.connection);
		//}
		return this.domicilio;
	}

	public String getTelefono() throws JRScriptletException {
		//if (this.telefono == null) {
			this.telefono = VariosDao.getConfiguracion("TELEFONO_MUNICIPIO", this.connection);
		//}
		return this.telefono;
	}

	public String getTelefonoConLeyenda() throws JRScriptletException {
		//if (this.telefonoConLeyenda == null) {
			this.telefonoConLeyenda = VariosDao.getConfiguracion("LEYENDA_FACTURAS_TEL", this.connection);
		//}
		return this.telefonoConLeyenda;
	}

	public String getLugaresDePago() throws JRScriptletException {
		//if (this.lugaresDePago == null) {
			this.lugaresDePago = VariosDao.getConfiguracion("LUGARES_PAGO_MUNICIPIO", this.connection);
		//}
		return this.lugaresDePago;
	}

	public String getLogo() throws JRScriptletException {
		//if (this.logo == null) {
			this.logo = VariosDao.getConfiguracion("LOGO_MUNICIPIO", this.connection).toLowerCase();
		//}
		return this.logo;
	}

	public String getLeyenda1() throws JRScriptletException {
		//if (this.leyenda1 == null) {
			this.leyenda1 = VariosDao.getConfiguracion("LEYENDA1", this.connection);
		//}
		return this.leyenda1;
	}

	public String getLeyenda2() throws JRScriptletException {
		//if (this.leyenda2 == null) {
			this.leyenda2 = VariosDao.getConfiguracion("LEYENDA2", this.connection);
		//}
		return this.leyenda2;
	}

	public String nombreDgr() throws JRScriptletException {
		//if (this.nombreDgr == null) {
			this.nombreDgr = VariosDao.getConfiguracion("NOMBRE_DGR", this.connection);
		//}
		return this.nombreDgr;
	}
}
