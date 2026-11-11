<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Nex_reports extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        
        // Check permission to access AR Reports
        if (staff_cant('view', 'ar_reports')) {
            access_denied('ar_reports');
        }
        
        $this->load->model('currencies_model'); 
        $this->load->library('app_modules');     
        
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);        
    }

    public function index()
    {
        $data['title'] = _l('nex_reports');
        $this->load->view('nex_reports_view', $data);
    }

    public function cashflow()
    {
        $data['title'] = _l('Cashflow');
        $this->load->view('cashflow', $data);
    }  
    
    public function income_table()
    {
        $this->app->get_table_data(module_views_path('nex_reports', 'tables/nex_income_table'));
    }    
    public function expense_table()
    {
        $this->app->get_table_data(module_views_path('nex_reports', 'tables/nex_expense_table'));
    }     
    
    public function purchase_expense_table()
    {
        $this->app->get_table_data(module_views_path('nex_reports', 'tables/purchase_expense_table'));
    }   
    
    public function debit_note_table()
    {
        $this->app->get_table_data(module_views_path('nex_reports', 'tables/debit_note_table'));
    }  
    
    public function cashflow_group($param=null)
    {
        if($param == 'income_table') {
            $this->app->get_table_data(module_views_path('nex_reports', 'tables/nex_income_table_group'));
            return true;
        }

        if($param == 'income_by_gid_modal') {
            $data['group_id'] = $this->input->get('group_id');
            $data['amount'] = $this->input->get('amount');
            $this->load->view('modals/income_by_gid_modal', $data);
            return true;
        }

        if($param == 'purchase_expense_by_gid_modal') {
            $data['group_id'] = $this->input->get('group_id');
            $data['amount'] = $this->input->get('amount');
            $this->load->view('modals/purchase_expense_by_gid_modal', $data);
            return true;
        }        

        if($param == 'purchase_expense_table') {
            $this->app->get_table_data(module_views_path('nex_reports', 'tables/nex_purchase_expense_table_group'));
            return true;
        }   
        
        if($param == 'expense_table') {
            $this->app->get_table_data(module_views_path('nex_reports', 'tables/nex_expense_table_group'));
            return true;
        }

        if($param == 'debit_note_table') {
            $this->app->get_table_data(module_views_path('nex_reports', 'tables/nex_debit_note_table_group'));
            return true;
        }

        if ($param == 'opening_balance') {
            $start_date = $this->input->get('start_date');
            if (!$start_date) {
                $start_date = date('Y-m-d');
            }

            $staff_id = get_staff_user_id();

            // Income before start date
            $this->db->select_sum('amount', 'total_amount');
            $this->db->from(db_prefix() . 'invoicepaymentrecords');
            $this->db->where('group_id IS NOT NULL AND group_id != ""');
            $this->db->where('date <', $start_date);
            if ($staff_id == 1) {
                $this->db->where('paymentmode IS NOT NULL');
                $this->db->where('paymentmode != ""');
                $this->db->where('paymentmode != "3"');
            }
            $income_before = (float) ($this->db->get()->row()->total_amount ?? 0);

            // Expense before start date
            $this->db->select_sum('tblexpenses.amount', 'total_amount');
            $this->db->from(db_prefix() . 'expenses');
            $this->db->where('tblexpenses.date <', $start_date);
            if ($staff_id == 1) {
                $this->db->where('tblexpenses.paymentmode IS NOT NULL');
                $this->db->where('tblexpenses.paymentmode != ""');
                $this->db->where('tblexpenses.paymentmode != 3');
            }
            $expense_before = (float) ($this->db->get()->row()->total_amount ?? 0);

            // Purchase expense before start date
            $this->db->select_sum('amount', 'total_amount');
            $this->db->from(db_prefix() . 'pur_invoice_payment');
            $this->db->where('group_id IS NOT NULL AND group_id != ""');
            $this->db->where('date <', $start_date);
            if ($staff_id == 1) {
                $this->db->where(db_prefix() . 'pur_invoice_payment.paymentmode IS NOT NULL');
                $this->db->where(db_prefix() . 'pur_invoice_payment.paymentmode != ""');
                $this->db->where(db_prefix() . 'pur_invoice_payment.paymentmode != 3');
            }
            $purchase_before = (float) ($this->db->get()->row()->total_amount ?? 0);

            // Debit notes before start date
            $this->db->select_sum('total', 'total_amount');
            $this->db->from(db_prefix() . 'pur_debit_notes');
            $this->db->where('date <', $start_date);
            if ($staff_id == 1) {
                $this->db->where('adminnote IS NOT NULL');
                $this->db->where('adminnote !=', '');
                $this->db->not_like('adminnote', 'Debit Note');
            }
            $debit_before = (float) ($this->db->get()->row()->total_amount ?? 0);

            $opening_balance_value = $income_before - ($expense_before + $purchase_before + $debit_before);

            $response = [
                'opening_balance' => app_format_money($opening_balance_value, get_base_currency()),
            ];

            echo json_encode($response);
            return true;
        }

        if ($param == 'delete_income_group') {
            $group_id = $this->input->post('group_id');
            if (!$group_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                return false;
            }

            $this->db->select('invoiceid');
            $this->db->where('group_id', $group_id);
            $invoices = $this->db->get(db_prefix() . 'invoicepaymentrecords')->result_array();
            $invoice_ids = array_unique(array_column($invoices, 'invoiceid'));

            $this->db->where('group_id', $group_id);
            $this->db->delete(db_prefix() . 'invoicepaymentrecords');
            
            if ($this->db->affected_rows() > 0) {
                $this->load->helper('invoices');
                foreach ($invoice_ids as $inv_id) {
                    if ($inv_id) {
                        update_invoice_status($inv_id);
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => 'Income payment deleted successfully']);
            return true;
        }

        if ($param == 'delete_purchase_expense_group') {
            // Get group id
            $group_id = $this->input->post('group_id');
            if (!$group_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                return false;
            }

            $this->db->select('pur_invoice');
            $this->db->where('group_id', $group_id);
            $pur_invoices = $this->db->get(db_prefix() . 'pur_invoice_payment')->result_array();
            $pur_invoice_ids = array_unique(array_column($pur_invoices, 'pur_invoice'));

            $this->db->where('group_id', $group_id);
            $this->db->delete(db_prefix() . 'pur_invoice_payment');

            if ($this->db->affected_rows() > 0) {
                foreach ($pur_invoice_ids as $pur_id) { 
                    if (!$pur_id) {
                        continue;
                    }

                    // Get invoice total directly
                    $pur_invoice = $this->db->where('id', $pur_id)->get(db_prefix() . 'pur_invoices')->row();

                    if ($pur_invoice) {
                        $invoice_total = $pur_invoice->total;
                        
                        // Calculate total paid (same as purinvoice_left_to_pay)
                        $this->db->where('pur_invoice', $pur_id);
                        $this->db->where('approval_status', 2);
                        $payments = $this->db->get(db_prefix() . 'pur_invoice_payment')->result_array();
                        
                        $total_paid = 0;
                        foreach ($payments as $p) {
                            $total_paid += $p['amount'];
                        }
                        
                        $left_to_pay = $invoice_total - $total_paid;
                        
                        // Apply same logic as delete_payment_pur_invoice
                        if ($left_to_pay > 0) {
                            $status_inv = 'partially_paid';
                            if ($left_to_pay == $invoice_total) {
                                $status_inv = 'unpaid';
                            }
                        } else {
                            $status_inv = 'paid';
                        }

                        $this->db->where('id', $pur_id);
                        $this->db->update(db_prefix() . 'pur_invoices', [
                            'payment_status' => $status_inv
                        ]);
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => 'Purchase expense payment deleted successfully']);
            return true;
        }

        $data['title'] = _l('Cashflow');
        $this->load->view('cashflow-group', $data);
    }   
    
    public function centralize_expense($param1 = "", $param2 = ""){

        if($param1 == 'components') {
            $data['param2'] = $param2;
            $this->load->view('components/expense/'.$this->input->post('type'), $data);
            return true;   
        }

        $this->load->view('modals/centralize_expense_modal', $data);
        return true;
    }
}