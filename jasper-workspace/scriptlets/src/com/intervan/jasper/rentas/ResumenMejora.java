package com.intervan.jasper.rentas;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.JasperOperation;

public class ResumenMejora extends BaseScriptlet {
	private Integer totalContribuyentes;
	private BigDecimal totalMetrosFrente;
	private BigDecimal totalSuperficie;
	private BigDecimal totalMonto;

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		final String idMejora = (String) this.getParameterValue("p_id_mejora");
		String query = "select count(*) total from (select distinct id_persona "
				+ "from re_detalles_mejora mejora, re_partidas partida, re_cuentas cuenta "
				+ "where mejora.id_mejora = ? and partida.id_partida = mejora.id_partida "
				+ "and cuenta.id_cuenta = partida.id_cuenta)";

		this.connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
				stmt.setString(1, idMejora);

				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {
					totalContribuyentes = rs.getInt("total");
				}

				return rs;
			}
		});

		query = "SELECT SUM(metros_frente) metros_frente, "
				+ "SUM(superficie * sup_porc_afectacion / 100) superficie, SUM(importe) importe "
				+ "FROM RE_DETALLES_MEJORA WHERE id_mejora = ?";

		this.connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
				stmt.setString(1, idMejora);

				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {
					totalMetrosFrente = rs.getBigDecimal("metros_frente");
					totalSuperficie = rs.getBigDecimal("superficie");
					totalMonto = rs.getBigDecimal("importe");
				}

				return rs;
			}
		});

	}

	public Integer totalContribuyentes() {
		return totalContribuyentes;
	}

	public BigDecimal totalMetrosFrente() {
		return totalMetrosFrente;
	}

	public BigDecimal totalSuperficie() {
		return totalSuperficie;
	}

	public BigDecimal totalMonto() {
		return totalMonto;
	}

	public BigDecimal montoPorMetroLineal() {
		return totalMonto.divide(new BigDecimal(2)).divide(totalMetrosFrente, 2, RoundingMode.HALF_UP);
	}

	public BigDecimal montoPorMetroCuadrado() {
		return totalMonto.divide(new BigDecimal(2)).divide(totalSuperficie, 2, RoundingMode.HALF_UP);
	}
}
