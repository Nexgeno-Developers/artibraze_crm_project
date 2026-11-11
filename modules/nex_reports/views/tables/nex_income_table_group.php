<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI =& get_instance();

$start_date = $CI->input->get('start_date') ?? date("Y-m-d");
$end_date   = $CI->input->get('end_date')   ?? date("Y-m-d");

// Get logged-in staff ID
$staff_id = get_staff_user_id();

// Staff filter: hide paymentmode = 3, NULL, empty for staff_id = 1
$where_staff_condition = '';
if ($staff_id == 1) {
    $where_staff_condition = " AND (ipr.paymentmode IS NOT NULL 
                                   AND ipr.paymentmode != '' 
                                   AND ipr.paymentmode != '3')";
}

/**
 * Build a subquery: GROUP BY group_id
 */
// $subQuery = "
//     (
//         SELECT 
//             group_id,
//             MAX(note) AS note,
//             MAX(date) AS payment_date,
//             MAX(transactionid) AS transactionid,
//             SUM(amount) AS amount
//         FROM " . db_prefix() . "invoicepaymentrecords
//         WHERE group_id IS NOT NULL AND group_id != ''
//         " . ($start_date ? "AND date >= '".$CI->db->escape_str($start_date)."'" : '') . "
//         " . ($end_date   ? "AND date <= '".$CI->db->escape_str($end_date)."'"   : '') . "
//         GROUP BY group_id
//     ) as grouped_payments
// ";
// $subQuery = "
//     (
//         SELECT 
//         MAX(date) AS payment_date,
//         MAX(note) AS note,
//         SUM(amount) AS amount,
//         group_id   
//         FROM " . db_prefix() . "invoicepaymentrecords
//         WHERE group_id IS NOT NULL AND group_id != ''
//         " . ($start_date ? "AND date >= '".$CI->db->escape_str($start_date)."'" : '') . "
//         " . ($end_date   ? "AND date <= '".$CI->db->escape_str($end_date)."'"   : '') . "
//         GROUP BY group_id
//     ) as grouped_payments
// ";

$subQuery = "
    (
        SELECT 
            MAX(ipr.date) AS payment_date,
            SUM(ipr.amount) AS amount,
            MAX(ipr.note) AS note,
            c.company AS client_company,
            ipr.group_id,
            inv.clientid AS clientid
        FROM " . db_prefix() . "invoicepaymentrecords ipr
        LEFT JOIN " . db_prefix() . "invoices inv ON inv.id = ipr.invoiceid
        LEFT JOIN " . db_prefix() . "clients c ON c.userid = inv.clientid
        WHERE ipr.group_id IS NOT NULL AND ipr.group_id != ''
        " . ($start_date ? "AND ipr.date >= '".$CI->db->escape_str($start_date)."'" : '') . "
        " . ($end_date ? "AND ipr.date <= '".$CI->db->escape_str($end_date)."'" : '') . "
        " . $where_staff_condition . "
        GROUP BY ipr.group_id
    ) as grouped_payments
";

// Columns (match the subquery aliases)
// $aColumns = [
//     'payment_date',
//     'note',
//     'amount',
//     'group_id',
//     //'transactionid',

// ];

$aColumns = [
    'payment_date',
    'amount',
    'client_company', 
    'note',
    'group_id',
    'clientid',
];

$sIndexColumn = 'group_id';
$sTable       = $subQuery; // <-- use the subquery as the table

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], []);
$output  = $result['output'];
$rResult = $result['rResult'];

/**
 * Calculate total income across groups
 */
$CI->db->select_sum('amount', 'total_amount');
$CI->db->from(db_prefix() . 'invoicepaymentrecords');
$CI->db->where('group_id IS NOT NULL AND group_id != ""');
if ($start_date) {
    $CI->db->where('date >=', $start_date);
}
if ($end_date) {
    $CI->db->where('date <=', $end_date);
}

// Apply same staff filter for totals
if ($staff_id == 1) {
    $CI->db->where('paymentmode IS NOT NULL');
    $CI->db->where('paymentmode != ""');
    $CI->db->where('paymentmode != "3"');
}

$query       = $CI->db->get();
$totalIncome = $query->row()->total_amount ?? 0;
$output['total_income'] = app_format_money($totalIncome, get_base_currency());

/**
 * Format rows
 */
foreach ($rResult as $aRow) {
    $row = [];

    $row[] = '<span data-order="' . strtotime($aRow['payment_date']) . '">' . _d($aRow['payment_date']) . '</span>';
    $row[] = '<a href="javascript:void(0)" onclick="income_by_gid(' 
          . $aRow['group_id'] 
          . ', \'' . app_format_money($aRow['amount'] ?? 0, get_base_currency()) . '\')">'
          . app_format_money($aRow['amount'] ?? 0, get_base_currency()) 
          . '</a>';
    $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '" target="_blank">' . $aRow['client_company'] . '</a>';
    $row[] = html_escape($aRow['note']) ?? '-';  
    $row[] = '<a href="javascript:void(0)" class="btn btn-danger btn-xs" onclick="delete_income_group(' . $aRow['group_id'] . ')"><i class="fa fa-trash"></i></a>';

    $output['aaData'][] = $row;
}

echo json_encode($output);
exit;
