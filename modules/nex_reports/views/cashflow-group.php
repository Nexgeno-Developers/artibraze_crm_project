<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<?php if(get_staff_user_id() == 1): ?>
<style>
  /* #debit-note-report{
    display: none;
  } */

  .table-income-table tbody tr td:nth-child(2) a {
    pointer-events: none;
    cursor: default;
    color: inherit; /* optional: keeps normal text color */
    text-decoration: none; /* optional: removes underline */
  }  

  .table-purchase-expense-table tbody tr td:nth-child(2) a {
    pointer-events: none;
    cursor: default;
    color: inherit; /* optional: keeps normal text color */
    text-decoration: none; /* optional: removes underline */
  }   
</style>
<?php endif; ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <!-- <label for="start_date">Start Date</label> -->
                                <input type="date" id="start_date" value="<?php echo date("Y-m-d") ?>" class="form-control" />
                            </div>
                            <div class="col-md-3">
                                <!-- <label for="end_date">End Date</label> -->
                                <input type="date" id="end_date" value="<?php echo date("Y-m-d") ?>" class="form-control" />
                            </div>
                            <div class="col-md-3 align-self-end">
                                <button id="filter-date" class="btn btn-primary">Filter</button>
                                <button id="reset-filter" class="btn btn-secondary">Reset</button>
                            </div>

                            <div class="col-md-3 align-self-end text-right">
                                <a href="javascript:void(0);" onclick="add_batch_payment();" id="" class="btn btn-success">Income</a>
                                <a href="javascript:void(0);" onclick="centralize_expense();" id="" class="btn btn-danger">Expense</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="panel_s">
                <div class="panel-body">
                    <h5 class="tw-mb-2 tw-mt-0 tw-font-bold">Financial Summary</h5>
                    <div class="tw-flex tw-flex-col tw-gap-1 text-sm">
                    <div class="tw-flex tw-justify-between">
                        <span>Opening Balance</span>
                        <span id="opening-balance" class="tw-font-medium">₹0.00</span>
                    </div>
                    <div class="tw-flex tw-justify-between">
                        <span>Total Income</span>
                        <span id="total-income" class="tw-font-medium">₹0.00</span>
                    </div>
                    <div class="tw-flex tw-justify-between">
                        <span>Total Purchase Expenses</span>
                        <span id="total-purchase-expense" class="tw-font-medium">₹0.00</span>
                    </div>                    
                    <div class="tw-flex tw-justify-between">
                        <span>Total Expenses</span>
                        <span id="total-expense" class="tw-font-medium">₹0.00</span>
                    </div>
                    <div class="tw-flex tw-justify-between">
                        <span>Total Debit Notes</span>
                        <span id="total-debit-note" class="tw-font-medium">₹0.00</span>
                    </div>                    
                    <!-- <hr> -->
                    <div class="tw-mt-2 tw-flex tw-justify-between">
                        <span>Net Movement (Period)</span>
                        <span id="period-net-balance" class="tw-font-medium">₹0.00</span>
                    </div>
                    <div class="tw-mt-2 tw-flex tw-justify-between tw-font-bold">
                        <span>Closing Balance</span>
                        <span id="net-balance">₹0.00</span>
                    </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="col-md-12" id="income-report">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="table-heading">Income Report</h5>
                        <?php
render_datatable([
                                 'Date',
                                 'Amount',
                                 'Customer',
                                 'Note',
                                 'Action',
                             ], 'income-table', ['table-small']);

                        ?>
                    </div>
                </div>
            </div>

            <div class="col-md-12" id="purchase-expense-report">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="table-heading">Purchase Expense Report</h5>
                        <?php
                            $table_data = [
                                'Date',
                                'Amount',
                                'Vendor',
                                'Note',
                                'Action',
                                //'GID',
                                //'Payment ID',
                                //'Purchase Invoice',
                                //'Payment Mode',
                                //'Transaction ID',
                                //'Vendor ID',
                            ];
                            render_datatable($table_data, 'purchase-expense-table', ['table-small']);
                        ?>
                    </div>
                </div>
            </div>            

            <div class="col-md-12" id="expense-report">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="table-heading">Expense Report</h5>
                        <?php
                        render_datatable([
                            'Date',
                            'Amount',
                            'Vendor',   
                            'Note',  
                            'Mode',
                            'Category',
                            'Payment #',                     
                            [
                                'name'    => 'Vendor ID',  
                                'th_attrs' => ['class' => 'not-export'],
                                'visible' => false, 
                            ]                           
                        ], 'expense-table', ['table-small']);
                        ?>
                    </div>
                </div>
            </div>   
            
            <div class="col-md-12" id="debit-note-report">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="table-heading">Debit Note Report</h5>
                        <?php
                        $table_columns = [
                            'Date',
                            'Amount',
                            'Vendor',
                            'Note',
                            'Status',
                            'Reference No',
                            'Remaining Amount',
                            'Debit Note Number',
                            'Debit Note Number Short', 
                            [
                                'name'    => 'Vendor ID',  
                                'th_attrs' => ['class' => 'not-export'],
                                'visible' => false, 
                            ] 
                        ];
                        render_datatable($table_columns, 'debit-note-table', ['table-small']);
                        ?>
                    </div>
                </div>
            </div>           
            <div class="col-md-12">
                <div id="modal-wrapper"></div>
            </div>           

        </div>
    </div>
</div>
<?php init_tail(); ?>
<style>
.table-heading {
    /* position: absolute; */
    /*top: 4%;*/
    /* left: 50%; */
    /* transform: translateX(-50%); */
    margin-top: 0;
    font-size: 16px;
    font-weight: 600;
    z-index: 10;
}

/* Reduce padding inside table cells only for .table-small */
.table-small > tbody > tr > td,
.table-small > tbody > tr > th,
.table-small > tfoot > tr > td,
.table-small > tfoot > tr > th,
.table-small > thead > tr > td,
.table-small > thead > tr > th {
    padding: 4px;
}

/* Smaller font size for .table-small */
.table-small {
    font-size: 12px;
}

/* Optional: adjust sorting icon alignment for .table-small */
.table-small.dataTable thead .sorting:after {
    content: "";
    height: 18px;
    margin-left: 5px;
    opacity: 0;
    top: 5px;
    transition: opacity 0.3s ease-out;
    width: 18px;
}
</style>

<script>

  // Function to calculate and update net balance
  function updateNetBalance() {
      const income = parseFloat($('#total-income').text().replace(/[^0-9.-]+/g,"")) || 0;
      const expense = parseFloat($('#total-expense').text().replace(/[^0-9.-]+/g,"")) || 0;
      const purchaseExpense = parseFloat($('#total-purchase-expense').text().replace(/[^0-9.-]+/g,"")) || 0;
      const debitNote = parseFloat($('#total-debit-note').text().replace(/[^0-9.-]+/g,"")) || 0;
      const openingBalance = parseFloat($('#opening-balance').text().replace(/[^0-9.-]+/g,"")) || 0;
      var currencySymbol = '<?php echo $this->currencies_model->get_base_currency()->symbol; ?>';
      
      // Movement in the selected period
      const periodNet = income - (expense + purchaseExpense + debitNote);
      // Closing balance = opening balance + period movement
      const netBalance = openingBalance + periodNet;
      
      // Format the number with 2 decimal places and currency symbol
      const formattedPeriodNet = currencySymbol + periodNet.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
      const formattedBalance = currencySymbol + netBalance.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
      
      // Update the period net and closing balance display
      const $periodNetElement = $('#period-net-balance');
      const $netBalanceElement = $('#net-balance');
      $periodNetElement.text(formattedPeriodNet);
      $netBalanceElement.text(formattedBalance);
      
      // Change color based on positive/negative
      if (netBalance >= 0) {
          $netBalanceElement.removeClass('text-danger').addClass('text-success');
      } else {
          $netBalanceElement.removeClass('text-success').addClass('text-danger');
      }
  }

  // Function to fetch and update opening balance
  function updateOpeningBalance() {
      const startDate = $('#start_date').val();

      $.get(admin_url + 'nex_reports/cashflow_group/opening_balance', { start_date: startDate })
          .done(function (response) {
              let data = response;

              if (typeof response === 'string') {
                  try {
                      data = JSON.parse(response);
                  } catch (e) {
                      return;
                  }
              }

              if (data.opening_balance) {
                  $('#opening-balance').text(data.opening_balance);
                  // Opening balance changed, recompute net balance
                  updateNetBalance();
              }
          });
  }

  // Reusable function to initialize a report table with filter/reset support
  function initTableWithFilter(selector, urlSegment, hiddenColumns = [], totalFieldId = null, totalFieldKey = null) {
    const table = initDataTable(selector, admin_url + 'nex_reports/' + urlSegment, [], [], {});
    
    table.page.len(10).draw();
    table.order([0, 'desc']).draw();
    //table.order([]).draw();

    if (totalFieldId && totalFieldKey) {
      table.on('xhr', function () {
        const json = table.ajax.json();
        if (json[totalFieldKey]) {
          if($('#' + totalFieldId).length > 0){
            $('#' + totalFieldId).html(json[totalFieldKey]);
            updateNetBalance();
          }
        }
      });
    }

    // Hide specified columns after DataTable is initialized
    if (hiddenColumns.length) {
      setTimeout(() => {
        hiddenColumns.forEach(index => {
          console.log(index);
          table.column(index).visible(false);
        });
      }, 1000);
    }

    // Date filter logic
    $('#filter-date').on('click', function () {
      const startDate = $('#start_date').val();
      const endDate = $('#end_date').val();
      const query = `?start_date=${startDate}&end_date=${endDate}`;
      updateOpeningBalance();
      table.ajax.url(admin_url + 'nex_reports/' + urlSegment + query).load();
    });

    // Reset logic
    $('#reset-filter').on('click', function () {
      $('#start_date').val('<?php echo date("Y-m-d") ?>');
      $('#end_date').val('<?php echo date("Y-m-d") ?>');
      updateOpeningBalance();
      table.ajax.url(admin_url + 'nex_reports/' + urlSegment).load();
    });
  }

  // Initialize all 3 reports on DOM ready
  $(function () {
    // Income Report
    initTableWithFilter(
      '.table-income-table',
      'cashflow_group/income_table',
      [],
      'total-income',
      'total_income'
    );

    // Purchase Expense Report
    initTableWithFilter(
      '.table-purchase-expense-table',
      'cashflow_group/purchase_expense_table',
      [],
      'total-purchase-expense',
      'total_expense'
    );    

    // Expense Report
    initTableWithFilter(
      '.table-expense-table',
      'cashflow_group/expense_table',
      [7],
      'total-expense',
      'total_expense'
    );

    // Debit Note Report
    initTableWithFilter(
        '.table-debit-note-table',
        'cashflow_group/debit_note_table',
        [7,8,9], // Column index to hide 
        'total-debit-note', // Total field ID
        'total_debits'     // JSON response key from server (matches your PHP code)
    );

    // Initial opening balance load
    updateOpeningBalance();
  });

    $(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');

    if (section) {
        const $target = $('#' + section);
        if ($target.length) {
        $('html, body').animate({
            scrollTop: $target.offset().top - 100 // Adjust offset as needed
        }, 800);
        }

        if(section == 'income-report') {
          add_batch_payment();
        }
    }
    }); 
    
function income_by_gid(groupId, amount) {
  // alert("Group ID: " + groupId);
  $("#modal-wrapper").load(
    admin_url + "nex_reports/cashflow_group/income_by_gid_modal?group_id=" + groupId + "&amount="+ amount,
    function () {
      if ($("#income-payment-modal").is(":hidden")) {
        $("#income-payment-modal").modal({
          backdrop: "static",
          show: true,
        });
      }
    }
  );    
}

function purchase_expense_by_gid(groupId, amount) {
  // alert("Group ID: " + groupId);
  $("#modal-wrapper").load(
    admin_url + "nex_reports/cashflow_group/purchase_expense_by_gid_modal?group_id=" + groupId + "&amount="+ amount,
    function () {
      if ($("#purchase-expense-payment-modal").is(":hidden")) {
        $("#purchase-expense-payment-modal").modal({
          backdrop: "static",
          show: true,
        });
      }
    }
  );    
}

function delete_income_group(groupId) {
  if (!confirm('Are you sure you want to delete this income group?')) {
    return;
  }

  $.ajax({
    url: admin_url + 'nex_reports/cashflow_group/delete_income_group',
    type: 'POST',
    dataType: 'json',
    data: { group_id: groupId },
    success: function(response) {
      if (response.success) {
        alert(response.message);
        $('.table-income-table').DataTable().ajax.reload(null, false);
        updateOpeningBalance();
      } else {
        alert(response.message);
      }
    },
    error: function() {
      alert('Failed to delete income group');
    }
  });
}

function delete_purchase_expense_group(groupId) {
  if (!confirm('Are you sure you want to delete this purchase expense group?')) {
    return;
  }

  $.ajax({
    url: admin_url + 'nex_reports/cashflow_group/delete_purchase_expense_group',
    type: 'POST',
    dataType: 'json',
    data: { group_id: groupId },
    success: function(response) {
      if (response.success) {
        alert(response.message);
        $('.table-purchase-expense-table').DataTable().ajax.reload(null, false);
        updateOpeningBalance();
      } else {
        alert(response.message);
      }
    },
    error: function() {
      alert('Failed to delete purchase expense group');
    }
  });
}

function centralize_expense() {
  $("#modal-wrapper").load(
    admin_url + "nex_reports/centralize_expense/",
    function () {
      if ($("#centralize-expense-payment-modal").is(":hidden")) {
        $("#centralize-expense-payment-modal").modal({
          backdrop: "static",
          show: true,
        });
      }
    }
  );    
}
</script>