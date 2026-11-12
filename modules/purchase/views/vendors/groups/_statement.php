<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-5 account-summary">
  <div class="text-right">
    <h4 class="no-margin bold"><?php echo _l('account_summary'); ?></h4>
    <p class="text-muted"><?php echo _l('statement_from_to',array($from,$to)); ?></p>
    <hr />
    <table class="table statement-account-summary">
      <tbody>
        <tr>
          <td class="text-left"><?php echo _l('statement_beginning_balance'); ?>:</td>
          <td><?php echo app_format_money($statement['beginning_balance'], $statement['currency']); ?></td>
        </tr>
        <tr>
          <td class="text-left"><?php echo _l('invoiced_amount'); ?>:</td>
          <td><?php echo app_format_money($statement['invoiced_amount'], $statement['currency']); ?></td>
        </tr>
        <tr>
          <td class="text-left"><?php echo _l('amount_paid'); ?>:</td>
          <td><?php echo app_format_money($statement['amount_paid'], $statement['currency']); ?></td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td class="text-left"><b><?php echo _l('balance_due'); ?></b>:</td>
          <td><?php echo app_format_money($statement['balance_due'], $statement['currency']); ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- <div class="col-md-12">
    <div class="text-center mb-3">
        <button id="btnShowGrouped" class="btn btn-secondary btn-sm">Grouped Statement</button>
        <button id="btnShowOriginal" class="btn btn-secondary btn-sm">Original Statement</button>
    </div>
</div> -->

<div class="col-md-12">
  <div class="text-center bold padding-10">
    <?php echo _l('customer_statement_info',array($from,$to)); ?>
  </div>
  <div class="table-responsive">
    <table id="originalTable" class="table table-bordered table-striped">
     <thead>
       <tr>
         <th><b><?php echo _l('statement_heading_date'); ?></b></th>
         <th><b><?php echo _l('statement_heading_details'); ?></b></th>
         <th class="text-right"><b><?php echo _l('statement_heading_payments'); ?></b></th>
         <th class="text-right"><b><?php echo _l('statement_heading_amount'); ?></b></b></th>
         <th class="text-right"><b><?php echo _l('statement_heading_balance'); ?></b></b></th>
       </tr>
     </thead>
     <tbody>
       <tr>
         <td><?php echo $from; ?></td>
         <td><?php echo _l('statement_beginning_balance'); ?></td>
         <td></td>
         <td class="text-right"><?php echo app_format_money($statement['beginning_balance'], $statement['currency'], true); ?></td>
         <td class="text-right"><?php echo app_format_money($statement['beginning_balance'], $statement['currency'], true); ?></td>
       </tr>
       <?php
       $tmpBeginningBalance = $statement['beginning_balance'];
       $tds_amount = 0;
       foreach($statement['result'] as $data){ ?>
         <tr>
           <td><?php echo _d($data['date']); ?></td>
           <td>
            <?php
            if(isset($data['invoice_id'])) {
              echo _l('statement_invoice_details',array('<a href="'.admin_url('purchase/purchase_invoice/'.$data['invoice_id']).'" target="_blank">'.get_pur_invoice_number($data['invoice_id']).'</a>',_d($data['duedate'])));
              if (!empty($data['challan_numbers'])) {
                echo ' - Challan: ' . pur_statement_challan_links($data['challan_numbers']);
              }
            } else if(isset($data['payment_id'])){
             echo _l('statement_payment_details',array('<a href="'.admin_url('purchase/payment_invoice/'.$data['payment_id']).'" target="_blank">'.'#'.$data['payment_id'].'</a>',get_pur_invoice_number($data['payment_invoice_id'])));

              $payment_mode = $this->db->select('paymentmode')->from(db_prefix().'pur_invoice_payment')->where('id', $data['payment_id'])->get()->row('paymentmode');
              $payment_mode = get_payment_mode_name_by_id($payment_mode);
              if (!empty($payment_mode)) {
                  echo '<br>';
                  echo 'Payment Mode : ' . e($payment_mode);

                  if(strtolower($payment_mode) == 'tds'){
                      $tds_amount += $data['payment_total'];
                  }
              } 

            } else if(isset($data['debit_note_id'])) {
            echo _l('statement_debit_note_details',array('<a href="'.admin_url('purchase/debit_notes/'.$data['debit_note_id']).'" target="_blank">'.format_debit_note_number($data['debit_note_id']).'</a>'));
          } else if(isset($data['debit_id'])) {
            echo _l('statement_debits_applied_details',array(
              '<a href="'.admin_url('purchase/debit_notes/'.$data['debit_applied_debit_note_id']).'" target="_blank">'.format_debit_note_number($data['debit_applied_debit_note_id']).'</a>',
              app_format_money($data['debit_amount'], $statement['currency'], true),
              get_pur_invoice_number($data['debit_invoice_id'])
            )
          );
          } else if(isset($data['debit_note_refund_id'])) {
            echo _l('statement_debit_note_refund', format_debit_note_number($data['refund_debit_note_id']));
          }
          ?>
        </td>
        
        <td class="text-right">
          <?php
          if(isset($data['payment_id'])) {
            echo app_format_money($data['payment_total'], $statement['currency'], true);
          } else if(isset($data['debit_note_refund_id'])) {
            echo app_format_money($data['refund_amount'], $statement['currency'], true);
          }
          ?>
        </td>
        <td class="text-right">
          <?php
          if(isset($data['invoice_id'])) {
            echo app_format_money($data['invoice_amount'], $statement['currency'], true);
          } else if(isset($data['debit_note_id'])) {
            echo app_format_money($data['debit_note_amount'], $statement['currency'], true);
          }
          ?>
        </td>
        <td class="text-right">
          <?php
          if(isset($data['payment_id'])) {
            $tmpBeginningBalance = ($tmpBeginningBalance + $data['payment_total']);
          } else if(isset($data['invoice_id'])){
            $tmpBeginningBalance = ($tmpBeginningBalance - $data['invoice_amount']);
          } else if(isset($data['debit_note_refund_id'])) {
            $tmpBeginningBalance = ($tmpBeginningBalance - $data['refund_amount']);
          } else if(isset($data['debit_note_id'])) {
            $tmpBeginningBalance = ($tmpBeginningBalance + $data['debit_note_amount']);
          }
          if(!isset($data['debit_id'])){
              echo app_format_money($tmpBeginningBalance, $statement['currency'], true);
          }
          ?>
        </td>
      </tr>
    <?php } ?>
  </tbody>
  <tfoot class="statement_tfoot">
   <tr>
     <td colspan="3" class="text-right">
       <b><?php echo _l('balance_due'); ?></b>
     </td>
     <td class="text-right" colspan="2">
       <b><?php echo app_format_money($statement['balance_due'], $statement['currency']); ?></b>
     </td>
   </tr>
    <tr>
        <td colspan="3" class="text-right">
            <b><?php echo _l('TDS'); ?></b>
        </td>
        <td class="text-right" colspan="2">
            <b><?php echo e(app_format_money($tds_amount, $statement['currency'])); ?></b>
        </td>
    </tr>   
 </tfoot>
</table>
</div>
</div>



<?php
// Group payments by group_id
$groupedPayments = [];
$otherRecords = [];

foreach ($statement['result'] as $data) {
    if (isset($data['payment_id']) && !empty($data['group_id'])) {
        $group_id = $data['group_id'];
        
        if (!isset($groupedPayments[$group_id])) {
            $groupedPayments[$group_id] = [
                'date' => $data['date'],
                'tmp_date' => $data['tmp_date'],
                'note' => $data['note'],
                'payment_total' => 0,
                'details' => []
            ];
        }
        
        $groupedPayments[$group_id]['payment_total'] += $data['payment_total'];
        $groupedPayments[$group_id]['details'][] = $data;
        
        // Update with the latest date/note if this record is newer
        if (strtotime($data['tmp_date']) > strtotime($groupedPayments[$group_id]['tmp_date'])) {
            $groupedPayments[$group_id]['date'] = $data['date'];
            $groupedPayments[$group_id]['tmp_date'] = $data['tmp_date'];
            $groupedPayments[$group_id]['note'] = $data['note'];
        }
    } else {
        $otherRecords[] = $data;
    }
}

// Combine grouped payments with other records and sort by date
$groupedResults = [];

// Add invoices and other records first
foreach ($otherRecords as $record) {
    $groupedResults[] = $record;
}

// Add grouped payments
foreach ($groupedPayments as $group_id => $group) {
    $groupedResults[] = [
        'is_grouped_payment' => true,
        'group_id' => $group_id,
        'date' => $group['date'],
        'tmp_date' => $group['tmp_date'],
        'note' => $group['note'],
        'payment_total' => $group['payment_total'],
        'details' => $group['details']
    ];
}

// Sort all records by date
usort($groupedResults, function($a, $b) {
    $a_date = isset($a['tmp_date']) ? $a['tmp_date'] : (isset($a['date']) ? $a['date'] : '');
    $b_date = isset($b['tmp_date']) ? $b['tmp_date'] : (isset($b['date']) ? $b['date'] : '');
    return strtotime($a_date) <=> strtotime($b_date);
});
?>

<!-- Grouped Table (Visible by Default) -->
<div class="col-md-12">
    <!-- <div class="text-center bold padding-10">
        <?php echo _l('customer_statement_info',array($from,$to)); ?>
    </div> -->
    <div class="table-responsive group-table-responsive">
        <table id="groupedTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th><b><?php echo _l('statement_heading_date'); ?></b></th>
                    <th><b><?php echo _l('statement_heading_details'); ?></b></th>
                    <th class="text-right"><b><?php echo _l('statement_heading_payments'); ?></b></th>
                    <th class="text-right"><b><?php echo _l('statement_heading_amount'); ?></b></b></th>
                    <th class="text-right"><b><?php echo _l('statement_heading_balance'); ?></b></b></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $from; ?></td>
                    <td><?php echo _l('statement_beginning_balance'); ?></td>
                    <td></td>
                    <td class="text-right"><?php echo app_format_money($statement['beginning_balance'], $statement['currency'], true); ?></td>
                    <td class="text-right"><?php echo app_format_money($statement['beginning_balance'], $statement['currency'], true); ?></td>
                </tr>
                <?php
                $tmpBeginningBalance = $statement['beginning_balance'];
                $tds_amount = 0;
                
                foreach($groupedResults as $data){ 
                ?>
                <tr>
                    <td><?php echo _d($data['date']); ?></td>
                    <td>
                        <?php
                        if(isset($data['invoice_id'])) {
                            echo _l('statement_invoice_details',array('<a href="'.admin_url('purchase/purchase_invoice/'.$data['invoice_id']).'" target="_blank">'.get_pur_invoice_number($data['invoice_id']).'</a>',_d($data['duedate'])));
                            if (!empty($data['challan_numbers'])) {
                                echo ' - Challan: ' . pur_statement_challan_links($data['challan_numbers']);
                            }
                        } else if(isset($data['is_grouped_payment'])) {
                            // Grouped payment
                            echo '<b>' . e($data['note']) . '</b><br>';
                            echo '<span style="font-size: 12px;">';
                            
                            foreach($data['details'] as $payment) {
                                echo _l('statement_payment_details',array(
                                    '<a href="'.admin_url('purchase/payment_invoice/'.$payment['payment_id']).'" target="_blank">'.'#'.$payment['payment_id'].'</a>',
                                    get_pur_invoice_number($payment['payment_invoice_id'])
                                ));
                                
                                $payment_mode = $this->db->select('paymentmode')->from(db_prefix().'pur_invoice_payment')->where('id', $payment['payment_id'])->get()->row('paymentmode');
                                $payment_mode = get_payment_mode_name_by_id($payment_mode);
                                if (!empty($payment_mode)) {
                                    echo ' (Mode: ' . e($payment_mode) . ')';
                                    if(strtolower($payment_mode) == 'tds'){
                                        $tds_amount += $payment['payment_total'];
                                    }
                                }
                                echo '<br>';
                            }
                            echo '</span>';
                        } else if(isset($data['payment_id'])) {
                            // Single payment (not grouped)
                            echo _l('statement_payment_details',array('<a href="'.admin_url('purchase/payment_invoice/'.$data['payment_id']).'" target="_blank">'.'#'.$data['payment_id'].'</a>',get_pur_invoice_number($data['payment_invoice_id'])));
                            
                            $payment_mode = $this->db->select('paymentmode')->from(db_prefix().'pur_invoice_payment')->where('id', $data['payment_id'])->get()->row('paymentmode');
                            $payment_mode = get_payment_mode_name_by_id($payment_mode);
                            if (!empty($payment_mode)) {
                                echo '<br>';
                                echo 'Payment Mode : ' . e($payment_mode);
                                
                                if(strtolower($payment_mode) == 'tds'){
                                    $tds_amount += $data['payment_total'];
                                }
                            }
                        } else if(isset($data['debit_note_id'])) {
                            echo _l('statement_debit_note_details',array('<a href="'.admin_url('purchase/debit_notes/'.$data['debit_note_id']).'" target="_blank">'.format_debit_note_number($data['debit_note_id']).'</a>'));
                        } else if(isset($data['debit_id'])) {
                            echo _l('statement_debits_applied_details',array(
                                '<a href="'.admin_url('purchase/debit_notes/'.$data['debit_applied_debit_note_id']).'" target="_blank">'.format_debit_note_number($data['debit_applied_debit_note_id']).'</a>',
                                app_format_money($data['debit_amount'], $statement['currency'], true),
                                get_pur_invoice_number($data['debit_invoice_id'])
                            ));
                        } else if(isset($data['debit_note_refund_id'])) {
                            echo _l('statement_debit_note_refund', format_debit_note_number($data['refund_debit_note_id']));
                        }
                        ?>
                    </td>
                    
                    <td class="text-right">
                        <?php
                        if(isset($data['payment_id']) && !isset($data['is_grouped_payment'])) { //this line not ging in condition
                            echo app_format_money($data['payment_total'], $statement['currency'], true);
                        } else if(isset($data['is_grouped_payment'])) {
                            echo app_format_money($data['payment_total'], $statement['currency'], true);
                        } else if(isset($data['debit_note_refund_id'])) {
                            echo app_format_money($data['refund_amount'], $statement['currency'], true);
                        }
                        ?>
                    </td>
                    <td class="text-right">
                        <?php
                        if(isset($data['invoice_id'])) {
                            echo app_format_money($data['invoice_amount'], $statement['currency'], true);
                        } else if(isset($data['debit_note_id'])) {
                            echo app_format_money($data['debit_note_amount'], $statement['currency'], true);
                        }
                        ?>
                    </td>
                    <td class="text-right">
                        <?php
                        if(isset($data['payment_id']) && !isset($data['is_grouped_payment'])) {
                            $tmpBeginningBalance = ($tmpBeginningBalance + $data['payment_total']);
                        } else if(isset($data['is_grouped_payment'])) {
                            $tmpBeginningBalance = ($tmpBeginningBalance + $data['payment_total']);
                        } else if(isset($data['invoice_id'])){
                            $tmpBeginningBalance = ($tmpBeginningBalance - $data['invoice_amount']);
                        } else if(isset($data['debit_note_refund_id'])) {
                            $tmpBeginningBalance = ($tmpBeginningBalance - $data['refund_amount']);
                        } else if(isset($data['debit_note_id'])) {
                            $tmpBeginningBalance = ($tmpBeginningBalance + $data['debit_note_amount']);
                        }
                        if(!isset($data['debit_id'])){
                            echo app_format_money($tmpBeginningBalance, $statement['currency'], true);
                        }
                        ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot class="statement_tfoot">
                <tr>
                    <td colspan="3" class="text-right">
                        <b><?php echo _l('balance_due'); ?></b>
                    </td>
                    <td class="text-right" colspan="2">
                        <b><?php echo app_format_money($statement['balance_due'], $statement['currency']); ?></b>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">
                        <b><?php echo _l('TDS'); ?></b>
                    </td>
                    <td class="text-right" colspan="2">
                        <b><?php echo e(app_format_money($tds_amount, $statement['currency'])); ?></b>
                    </td>
                </tr>   
            </tfoot>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    // By default, grouped table is visible
    $("#groupedTable").show();
    $("#originalTable").hide();
    $("#btnShowGrouped").focus();

    // Show Grouped Table
    $("body").on("click", "#btnShowGrouped", function() {
        $("#originalTable").hide();
        $("#groupedTable").show();
    });

    // Show Original Table
    $("body").on("click", "#btnShowOriginal", function() {
        $("#groupedTable").hide();
        $("#originalTable").show();
    });
});
</script>
<style>
table.table {
    margin-top: 25px;
    font-size: 12px;
}
</style>

<!--Printable Area Code-->
<div id="printArea" style="display:none">
  <div class="organization-printable"></div>
  <div class="row">
    <div class="col-md-6 vendor-printable"></div>
    <div class="col-md-6 summery-printable"></div>
  </div>
  <div class="text-center"><?php echo _l('customer_statement_info',array($from,$to)); ?></div>
  <div class="table-printable"></div>
</div>
<style>
  /* Print: Remove hyperlink styles and URLs */
  @media print {
    a[href]:after {
      content: none !important;
    }
    a {
      text-decoration: none !important;
      color: black !important;
    }
  }

  /* Print: Force Bootstrap grid columns to behave */
  @media print {
    .col-md-6 {
      float: left;
      width: 50% !important;
    }
    .row::after {
      content: "";
      display: table;
      clear: both;
    }
  }

@media print {
  .no-print,
  .page-title,
  header,
  title,
  footer {
    display: none !important;
    visibility: hidden !important;
  }
}  
</style>


<script src="https://cdnjs.cloudflare.com/ajax/libs/printThis/1.15.0/printThis.min.js"></script>

<script>
  $("#printBtn").off("click").on("click", function () {
    // Fill print sections with dynamic HTML
    $(".table-printable").html($(".group-table-responsive").html());
    $(".organization-printable").html($(".format_organization_info").html());
    $(".vendor-printable").html($(".format_vendor_info").html());
    $(".summery-printable").html($(".account-summary").html());

    $("#printArea").show();

    // Print after DOM updates
    setTimeout(function () {
      $("#printArea").printThis({
        importCSS: true,
        importStyle: true,
        beforePrintEvent: function ($iframe) {
          // Strip links inside the print iframe
          $iframe.find("a").each(function () {
            var $link = $(this);
            var text = $link.text();
            $link.replaceWith(text);
          });
        },
        afterPrint: function () {
            // Hide again after printing
            $("#printArea").hide();
        } 
      });
    }, 500); // Delay ensures updated DOM is cloned
  });
</script>
<!--Printable Area Code-->