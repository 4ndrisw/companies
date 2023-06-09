<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'formatted_number',
    'company',
    'date',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'companies';


$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'companies.clientid',
    //'LEFT JOIN ' . db_prefix() . 'programs ON ' . db_prefix() . 'programs.id = ' . db_prefix() . 'companies.program_id',
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], ['id']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'formatted_number') {
            $_data = '<a href="' . admin_url('companies/company/' . $aRow['id']) . '">' . $_data . '</a>';
            $_data .= '<div class="row-options">';
            $_data .= '<a href="' . admin_url('companies/update/' . $aRow['id']) . '">' . _l('edit') . '</a>';

            if (staff_can('delete', 'companies')) {
                $_data .= ' | <a href="' . admin_url('companies/delete/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $_data .= '</div>';
        } elseif ($aColumns[$i] == 'date') {
            $_data = _d($_data);
        } 
        $row[] = $_data;

    }
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
