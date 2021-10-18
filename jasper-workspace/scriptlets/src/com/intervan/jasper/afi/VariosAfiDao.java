package com.intervan.jasper.afi;

import java.sql.Date;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.JasperConnection;
import com.intervan.jasper.general.JasperOperation;

/**
 * Consulta datos necesarios en la mayoría de los reportes.
 * 
 * @author ddiluca
 **/

public class VariosAfiDao {
	private static UnidadAdministracion unidadAdministracion = new UnidadAdministracion();
	private static UnidadEjecutora unidadEjecutora = new UnidadEjecutora();
	private static Ejercicio ejercicio = new Ejercicio();

	public static UnidadAdministracion getUnidadAdministracion(
			final String codUnidadAdministracion,
			final JasperConnection connection) throws JRScriptletException {
		String query = "SELECT cod_unidad_administracion, descripcion, control_financiero, activa "
				+ "FROM kr_unidades_administracion "
				+ "WHERE cod_unidad_administracion = ?";

		connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt)
					throws JRScriptletException, SQLException {
				stmt.setString(1, codUnidadAdministracion);

				ResultSet rs = stmt.executeQuery();

				rs.next();

				unidadAdministracion.cod = (Integer) rs.getInt(1);
				unidadAdministracion.descripcion = (String) rs.getString(2);
				unidadAdministracion.controlFinanciero = (String) rs
						.getString(3);
				unidadAdministracion.activa = (String) rs.getString(4);

				return rs;
			}
		});
		return unidadAdministracion;
	}

	public static UnidadEjecutora getUnidadEjecutora(
			final String codUnidadEjecutora, final JasperConnection connection)
			throws JRScriptletException {
		String query = "SELECT cod_unidad_ejecutora, descripcion, cod_unidad_administracion, activa "
				+ "		  FROM kr_unidades_ejecutoras"
				+ "        WHERE cod_unidad_ejecutora = ?";

		connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt)
					throws JRScriptletException, SQLException {
				stmt.setString(1, codUnidadEjecutora);

				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {

					unidadEjecutora.cod = (Integer) rs.getInt(1);
					unidadEjecutora.descripcion = (String) rs.getString(2);
					unidadEjecutora.codUnidadAdministracion = (Integer) rs
							.getInt(3);
					unidadEjecutora.activa = (String) rs.getString(4);
				}

				return rs;
			}
		});
		return unidadEjecutora;
	}

	public static Ejercicio getEjercicio(final String idEjercicio,
			final JasperConnection connection) throws JRScriptletException {
		String query = "SELECT id_ejercicio, nro_ejercicio, descripcion, id_estructura, "
				+ "			   fecha_inicio,fecha_fin, cant_periodos, cant_periodos_cf, "
				+ "			   reconducido, abierto,fecha_abierto, cerrado, fecha_cerrado, "
				+ "			   control_financiero,tipo_periodo, tipo_periodo_cf, nivel "
				+ "		  FROM kr_ejercicios " + "		 WHERE id_ejercicio = ? ";

		connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt)
					throws JRScriptletException, SQLException {
				stmt.setString(1, idEjercicio);

				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {
					ejercicio.idEjercicio = (Integer) rs.getInt(1);
					ejercicio.nroEjercicio = (Integer) rs.getInt(2);
					ejercicio.descripcion = (String) rs.getString(3);
					ejercicio.idEstructura = (Integer) rs.getInt(4);
					ejercicio.fechaInicio = (Date) rs.getDate(5);
					ejercicio.fechaFin = (Date) rs.getDate(6);
					ejercicio.cantPeriodos = (Integer) rs.getInt(7);
					ejercicio.cantPeriodosCf = (Integer) rs.getInt(8);
					ejercicio.reconducido = (String) rs.getString(9);
					ejercicio.abierto = (String) rs.getString(10);
					ejercicio.fechaAbierto = (Date) rs.getDate(11);
					ejercicio.cerrado = (String) rs.getString(12);
					ejercicio.fechaCerrado = (Date) rs.getDate(13);
					ejercicio.controlFinanciero = (String) rs.getString(14);
					ejercicio.tipoPeriodo = (String) rs.getString(15);
					ejercicio.tipoPeriodoCf = (String) rs.getString(16);
					ejercicio.nivel = (Integer) rs.getInt(17);
				}
				return rs;
			}
		});
		return ejercicio;
	}
}

class UnidadAdministracion {
	public int cod;
	public String descripcion;
	public String controlFinanciero;
	public String activa;

	public UnidadAdministracion() {
		super();
	}
}

class UnidadEjecutora {
	public int cod;
	public String descripcion;
	public int codUnidadAdministracion;
	public String activa;

	public UnidadEjecutora() {
		super();
	}
}

class Ejercicio {
	public int idEjercicio;
	public int nroEjercicio;
	public String descripcion;
	public int idEstructura;
	public Date fechaInicio;
	public Date fechaFin;
	public int cantPeriodos;
	public int cantPeriodosCf;
	public String reconducido;
	public String abierto;
	public Date fechaAbierto;
	public String cerrado;
	public Date fechaCerrado;
	public String controlFinanciero;
	public String tipoPeriodo;
	public String tipoPeriodoCf;
	public int nivel;

	public Ejercicio() {
		super();
	}
}

