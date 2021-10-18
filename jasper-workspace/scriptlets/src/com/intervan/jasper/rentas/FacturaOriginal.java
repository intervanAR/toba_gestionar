package com.intervan.jasper.rentas;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.rentas.daos.VariosDao;

public class FacturaOriginal extends BaseScriptlet {
	private Boolean imprimeBanelco;
	private Boolean imprimeNroDebitoTn;

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		this.imprimeBanelco = VariosDao.getConfiguracion("IMPRIME_BANELCO", this.connection, "S").equals("S");
		this.imprimeNroDebitoTn = VariosDao.getConfiguracion("IMPRIME_DEBITO", this.connection, "S").equals("S");
	}

	public Boolean imprimeBanelco() {
		return this.imprimeBanelco;
	}

	public Boolean imprimeNroDebitoTn() {
		return this.imprimeNroDebitoTn;
	}
}
