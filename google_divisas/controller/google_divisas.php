<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @copyright 2016-2017, Carlos García Gómez. All Rights Reserved. 
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of google_divisas
 *
 * @author carlos
 */
class google_divisas extends fs_controller
{

    public $setup_cron;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Google Divisas', 'admin', FALSE, FALSE);
    }

    protected function private_core()
    {
        $this->share_extensions();

        $fsvar = new fs_var();
        $this->setup_cron = $fsvar->simple_get('google_divisas_cron');

        if (isset($_GET['consultar'])) {
            $divisa = new divisa();
            foreach ($divisa->all() as $div) {
                if ($div->coddivisa != 'EUR') {
                    $div->tasaconv_compra = $div->tasaconv = $this->convert_currency(1, 'EUR', $div->coddivisa);
                    $div->save();
                }
            }

            $this->new_message('Tasas de conversión actualizadas. '
                . '<a href="index.php?page=admin_divisas" target="_parent">Recarga la página</a>.');
        } else if (isset($_GET['cron'])) {
            $this->setup_cron = '1';
            $fsvar->simple_save('google_divisas_cron', $this->setup_cron);
            $this->new_message('Cron activado.');
        } else if (isset($_GET['nocron'])) {
            $this->setup_cron = FALSE;
            $fsvar->simple_delete('google_divisas_cron');
            $this->new_message('Cron desactivado.');
        }
    }

    private function share_extensions()
    {
        $fsext = new fs_extension();
        $fsext->name = 'tab_divisas';
        $fsext->from = __CLASS__;
        $fsext->to = 'admin_divisas';
        $fsext->type = 'modal';
        $fsext->text = '<i class="fa fa-globe"></i><span class="hidden-xs">&nbsp; Google</span>';
        $fsext->save();
    }

    private function convert_currency($amount, $from, $to)
    {
        $url = "http://free.currencyconverterapi.com/api/v5/convert?q=" . $from . "_" . $to . "&compact=ultra";
        $data = fs_file_get_contents($url);
        $json = json_decode($data, true);

        $tasa = 1;
        if (isset($json[$from . '_' . $to])) {
            $tasa = (float) $json[$from . '_' . $to];
        }

        return $amount * $tasa;
    }
}
