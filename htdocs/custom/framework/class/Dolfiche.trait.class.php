<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */


trait DolFiche

{

	public function set_dol_fiche_head($links=array(), $active='0', $title='', $notab=0, $picto='', $pictoisfullpath=0, $morehtmlright='')
	{

		$this->dfh_links = $links;
		$this->dfh_active = $active;
		$this->dfh_title = $title;
		$this->dfh_notab = $notab;
		$this->dfh_picto = $picto;
		$this->dfh_pictoisfullpath = $pictoisfullpath;
		$this->dfh_morehtmlright = $morehtmlright;


	}

	public function dol_fiche_head()
	{

		return dol_get_fiche_head($this->dfh_links, $this->dfh_active, $this->dfh_title, $this->dfh_notab, $this->dfh_picto, $this->dfh_pictoisfullpath, $this->dfh_morehtmlright);

	}

	public function dol_fiche_end()

	{

		return dol_get_fiche_end();

	}

}
