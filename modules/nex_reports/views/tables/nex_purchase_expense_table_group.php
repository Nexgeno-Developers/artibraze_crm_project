<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI =& get_instance();

$start_date = $CI->input->get('start_date') ?? date("Y-m-d");
$end_date   = $CI->input->get('end_date')   ?? date("Y-m-d");

/**
 * Subquery with GROUP BY group_id
 */
// $subQuery = "
//     (
//         SELECT 
//             group_id,
//             MAX(" . db_prefix() . "pur_invoice_payment.id) AS payment_id,
//             MAX(" . db_prefix() . "pur_invoice_payment.date) AS pay_date,
//             SUM(" . db_prefix() . "pur_invoice_payment.amount) AS amount,
//             MAX(" . db_prefix() . "pur_invoices.invoice_number) AS pur_invoice_id,
//             MAX(" . db_prefix() . "payment_modes.name) AS paymentmode,
//             MAX(" . db_prefix() . "pur_invoice_payment.transactionid) AS transactionid,
//             MAX(" . db_prefix() . "pur_invoice_payment.note) AS note,
//             MAX(" . db_prefix() . "pur_vendor.company) AS vendor_company,
//             MAX(" . db_prefix() . "pur_vendor.userid) AS vendor_id
//         FROM " . db_prefix() . "pur_invoice_payment
//         LEFT JOIN " . db_prefix() . "pur_invoices 
//             ON " . db_prefix() . "pur_invoices.id = " . db_prefix() . "pur_invoice_payment.pur_invoice
//         LEFT JOIN " . db_prefix() . "pur_vendor 
//             ON " . db_prefix() . "pur_vendor.userid = " . db_prefix() . "pur_invoices.vendor
//         LEFT JOIN " . db_prefix() . "payment_modes 
//             ON " . db_prefix() . "payment_modes.id = " . db_prefix() . "pur_invoice_payment.paymentmode
//         WHERE group_id IS NOT NULL AND group_id != ''
//         " . ($start_date ? "AND " . db_prefix() . "pur_invoice_payment.date >= '".$CI->db->escape_str($start_date)."'" : '') . "
//         " . ($end_date   ? "AND " . db_prefix() . "pur_invoice_payment.date <= '".$CI->db->escape_str($end_date)."'"   : '') . "
//         GROUP BY group_id
//     ) as grouped_expenses
// ";


// Get logged-in staff ID
$staff_id = get_staff_user_id();

// Add condition: if staff ID is 1, hide rows with paymentmode = 3, null, or empty
$where_staff_condition = '';
if ($staff_id == 1) {
    $where_staff_condition = " AND (" . db_prefix() . "pur_invoice_payment.paymentmode IS NOT NULL 
                               AND " . db_prefix() . "pur_invoice_payment.paymentmode != '' 
                               AND " . db_prefix() . "pur_invoice_payment.paymentmode != 3)";
}


$subQuery = "
    (
        SELECT 
            MAX(" . db_prefix() . "pur_invoice_payment.date) AS pay_date,
            SUM(" . db_prefix() . "pur_invoice_payment.amount) AS amount,
            MAX(" . db_prefix() . "pur_vendor.company) AS vendor_company,
            MAX(" . db_prefix() . "pur_invoice_payment.note) AS note,
            MAX(" . db_prefix() . "pur_vendor.userid) AS vendor_id,      
            group_id
        FROM " . db_prefix() . "pur_invoice_payment
        LEFT JOIN " . db_prefix() . "pur_invoices 
            ON " . db_prefix() . "pur_invoices.id = " . db_prefix() . "pur_invoice_payment.pur_invoice
        LEFT JOIN " . db_prefix() . "pur_vendor 
            ON " . db_prefix() . "pur_vendor.userid = " . db_prefix() . "pur_invoices.vendor
        LEFT JOIN " . db_prefix() . "payment_modes 
            ON " . db_prefix() . "payment_modes.id = " . db_prefix() . "pur_invoice_payment.paymentmode
        WHERE group_id IS NOT NULL AND group_id != ''
        " . ($start_date ? "AND " . db_prefix() . "pur_invoice_payment.date >= '".$CI->db->escape_str($start_date)."'" : '') . "
        " . ($end_date   ? "AND " . db_prefix() . "pur_invoice_payment.date <= '".$CI->db->escape_str($end_date)."'"   : '') . "
        " . $where_staff_condition . "
        GROUP BY group_id
    ) as grouped_expenses
";

// Columns must match aliases from subquery
$aColumns = [
    'pay_date',
    'amount',
    'vendor_company',
    'note',
    'group_id',
    // 'payment_id',
    // 'pur_invoice_id',
    // 'paymentmode',
    // 'transactionid',
    // 'vendor_id'
];

$sIndexColumn = 'group_id';
$sTable       = $subQuery;

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], []);
$output  = $result['output'];
$rResult = $result['rResult'];

/**
 * Total Purchase Expense (all grouped payments)
 */
$CI->db->select_sum('amount', 'total_amount');
$CI->db->from(db_prefix() . 'pur_invoice_payment');
$CI->db->where('group_id IS NOT NULL AND group_id != ""');
if ($start_date) {
    $CI->db->where('date >=', $start_date);
}
if ($end_date) {
    $CI->db->where('date <=', $end_date);
}

// Apply same staff filter for total
if ($staff_id == 1) {
    $CI->db->where(db_prefix() . 'pur_invoice_payment.paymentmode IS NOT NULL');
    $CI->db->where(db_prefix() . 'pur_invoice_payment.paymentmode != ""');
    $CI->db->where(db_prefix() . 'pur_invoice_payment.paymentmode != 3');
}

$query = $CI->db->get();
$totalExpense = $query->row()->total_amount ?? 0;
$output['total_expense'] = app_format_money($totalExpense, get_base_currency());

/**
 * Format datatable rows
 */
foreach ($rResult as $aRow) {
    $row = [];
    $row[] = '<span data-order="' . strtotime($aRow['pay_date']) . '">' . _d($aRow['pay_date']) . '</span>';
$row[] = '<a href="javascript:void(0)" onclick="purchase_expense_by_gid(' 
          . $aRow['group_id'] 
          . ', \'' . app_format_money($aRow['amount'] ?? 0, get_base_currency()) . '\')">'
          . app_format_money($aRow['amount'] ?? 0, get_base_currency()) 
          . '</a>'; 
    $row[] = '<a href="' . admin_url('purchase/vendor/' . $aRow['vendor_id']) . '" target="_blank">' . $aRow['vendor_company'] . '</a>';
    $row[] = $aRow['note'] ?? '-';   
    $row[] = '<a href="javascript:void(0)" class="btn btn-danger btn-xs" onclick="delete_purchase_expense_group(' . $aRow['group_id'] . ')"><i class="fa fa-trash"></i></a>';

    $output['aaData'][] = $row;
}

echo json_encode($output);
exit;
