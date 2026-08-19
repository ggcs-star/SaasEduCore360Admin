<div class="content-wrapper" style="min-height: 946px; background: #f4f6f9;">
    <section class="content">
        
        <!-- ========================================= -->
        <!-- 1. NOTIFICATIONS -->
        <!-- ========================================= -->
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-md-12">
                <?php foreach ($notifications as $notice_key => $notice_value) { ?>
                    <div class="dashalert alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close close_notice" data-dismiss="alert" aria-label="Close" data-noticeid="<?php echo $notice_value->id; ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <a href="<?php echo site_url('admin/notification') ?>">
                            <i class="fa fa-bell"></i> <?php echo $notice_value->title; ?>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>

        <?php
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        $bar_chart = false;
        $line_chart = false;
        
        $recent_transactions = array();

        $this->db->order_by('date', 'DESC');
        $this->db->limit(3);
        $expenses = $this->db->get('expenses')->result_array();
        foreach($expenses as $row) {
            $row['type'] = 'Expense';
            $recent_transactions[] = $row;
        }

        $this->db->order_by('date', 'DESC');
        $this->db->limit(3);
        $incomes = $this->db->get('income')->result_array();
        foreach($incomes as $row) {
            $row['type'] = 'Income';
            $recent_transactions[] = $row;
        }

        usort($recent_transactions, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        $recent_transactions = array_slice($recent_transactions, 0, 8);

        $total_income_session = 0;
        $total_expenses_session = 0;

        if ($this->db->table_exists('income')) {
            $this->db->select_sum('amount');
            $q_inc = $this->db->get('income');
            $total_income_session = $q_inc->row()->amount ?? 0;
        }

        if ($this->db->table_exists('expenses')) {
            $this->db->select_sum('amount');
            $q_exp = $this->db->get('expenses');
            $total_expenses_session = $q_exp->row()->amount ?? 0;
        }
        ?>

        <!-- ========================================= -->
        <!-- 2. TOP 5 MAIN CARDS -->
        <!-- ========================================= -->
        <div class="cards-row">
            <div class="col-md-custom-5">
                <div class="stat-card stat-card-purple">
                    <div class="stat-card-icon"><i class="fa fa-money"></i></div>
                    <div class="stat-card-content">
                        <span class="stat-label">Monthly Fees</span>
                        <h3 class="stat-number"><?php echo $currency_symbol . $month_collection; ?></h3>
                        <span class="stat-trend up"><i class="fa fa-arrow-up"></i> 10%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-custom-5">
                <div class="stat-card stat-card-green">
                    <div class="stat-card-icon"><i class="fa fa-credit-card"></i></div>
                    <div class="stat-card-content">
                        <span class="stat-label">Monthly Expenses</span>
                        <h3 class="stat-number"><?php echo $currency_symbol . $month_expense; ?></h3>
                        <span class="stat-trend down"><i class="fa fa-arrow-down"></i> 10%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-custom-5">
                <div class="stat-card stat-card-blue">
                    <div class="stat-card-icon"><i class="fa fa-users"></i></div>
                    <div class="stat-card-content">
                        <span class="stat-label">Students</span>
                        <h3 class="stat-number"><?php echo $total_students; ?></h3>
                        <span class="stat-trend up"><i class="fa fa-arrow-up"></i> 10%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-custom-5">
                <div class="stat-card stat-card-pink">
                    <div class="stat-card-icon"><i class="fa fa-user"></i></div>
                    <div class="stat-card-content">
                        <span class="stat-label">Teachers</span>
                        <h3 class="stat-number"><?php echo isset($roles['Teacher']) ? $roles['Teacher'] : 0; ?></h3>
                        <span class="stat-trend up"><i class="fa fa-arrow-up"></i> 10%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-custom-5">
                <div class="stat-card stat-card-orange">
                    <div class="stat-card-icon"><i class="fa fa-user-plus"></i></div>
                    <div class="stat-card-content">
                        <span class="stat-label">Super Admin</span>
                        <h3 class="stat-number"><?php echo isset($roles['Super Admin']) ? $roles['Super Admin'] : 0; ?></h3>
                        <span class="stat-trend up"><i class="fa fa-arrow-up"></i> 10%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================= -->
        <!-- 3. STAFF CARDS -->
        <!-- ========================================= -->
        <div class="cards-row">
            <?php
            $excluded_roles = ['Teacher', 'Super Admin'];
            $staff_roles = array();
            foreach ($roles as $key => $value) {
                if (!in_array($key, $excluded_roles)) {
                    $staff_roles[$key] = $value;
                }
            }
            $staff_roles_array = array_slice($staff_roles, 0, 5, true);
            ?>
            <?php foreach ($staff_roles_array as $key => $value) { ?>
            <div class="col-md-custom-5">
                <div class="stat-card stat-card-gray">
                    <div class="stat-card-icon"><i class="fa fa-user"></i></div>
                    <div class="stat-card-content">
                        <span class="stat-label"><?php echo $key; ?></span>
                        <h3 class="stat-number"><?php echo $value; ?></h3>
                        <span class="stat-trend up">Active</span>
                    </div>
                </div>
            </div>
            <?php } ?>
            <?php for ($i = 0; $i < (5 - count($staff_roles_array)); $i++) { ?>
            <div class="col-md-custom-5">
                <div class="stat-card stat-card-gray">
                    <div class="stat-card-icon"><i class="fa fa-user"></i></div>
                    <div class="stat-card-content">
                        <span class="stat-label">-</span>
                        <h3 class="stat-number">0</h3>
                        <span class="stat-trend up">Active</span>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>

        <!-- ========================================= -->
        <!-- 4. TWO CHARTS -->
        <!-- ========================================= -->
        <div class="row d-flex align-items-stretch"> 
            <?php if (($this->module_lib->hasActive('fees_collection')) && ($this->module_lib->hasActive('expense'))) {
                if ($this->rbac->hasPrivilege('fees_collection_and_expense_monthly_chart', 'can_view')) { $bar_chart = true; ?>
            <div class="col-md-6">
                <div class="chart-card chart-card-fees">
                    <div class="chart-card-header">
                        <h4><i class="fa fa-chart-area"></i> Fees Collection & Expenses <span style="font-size:12px;font-weight:400;color:#636e72;">(<?php echo date('F Y'); ?>)</span></h4>
                        <div class="chart-legend">
                            <span><span class="legend-dot collection-dot"></span> Fees</span>
                            <span><span class="legend-dot expense-dot"></span> Expenses</span>
                        </div>
                    </div>
                    <div id="monthlyChart" class="chart-container"></div>
                </div>
            </div>
            <?php } if ($this->rbac->hasPrivilege('fees_collection_and_expense_yearly_chart', 'can_view')) { $line_chart = true; ?>
            <div class="col-md-6">
                <div class="chart-card chart-card-trend">
                    <div class="chart-card-header">
                        <h4><i class="fa fa-chart-line"></i> Collection Trend (Session)</h4>
                    </div>
                    <div id="sessionChart" class="chart-container"></div>
                </div>
            </div>
            <?php } } ?>
        </div>

        <!-- ========================================= -->
        <!-- 5. DONUT CHART (33%) + TRANSACTIONS (67%) -->
        <!-- ========================================= -->
        <div class="donut-transactions-row">
            <div class="donut-col">
                <div class="chart-card h-100" style="margin-bottom:0;">
                    <h5 style="font-weight: 600; color: #2d3436; text-align: center; margin-bottom: 16px;">
                        <i class="fa fa-pie-chart" style="color: #6c5ce7; margin-right: 8px;"></i> Income vs Expenses
                    </h5>
                    <div id="donutChart" style="height:200px; width:100%;"></div>
                    <div class="donut-summary">
                        <div class="donut-item">
                            <span class="donut-dot income"></span>
                            <span class="label">Income</span>
                            <div class="value"><?php echo $currency_symbol . number_format($total_income_session); ?></div>
                        </div>
                        <div class="donut-item">
                            <span class="donut-dot expense"></span>
                            <span class="label">Expenses</span>
                            <div class="value"><?php echo $currency_symbol . number_format($total_expenses_session); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="transactions-col">
                <div class="chart-card h-100" style="margin-bottom:0;">
                    <h5 style="font-weight: 600; color: #2d3436; margin: 0 0 12px 0;">
                        <i class="fa fa-list-alt" style="color: #6c5ce7; margin-right: 8px;"></i> Recent Transactions
                    </h5>
                    <table class="table table-borderless transaction-table">
                        <tbody>
                            <?php if(!empty($recent_transactions)): ?>
                                <?php foreach(array_slice($recent_transactions, 0, 8) as $t): ?>
                                <tr>
                                    <td style="width: 100px;">
                                        <span class="badge-transaction badge-<?php echo ($t['type'] == 'Expense') ? 'expense' : 'income'; ?>">
                                            <?php echo $t['type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $t['name']; ?></td>
                                    <td class="text-right transaction-amount"><?php echo $currency_symbol . number_format($t['amount']); ?></td>
                                    <td class="text-right transaction-date" style="width: 100px;"><?php echo date('M d, Y', strtotime($t['date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No recent transactions found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================= -->
        <!-- 6. CALENDAR -->
        <!-- ========================================= -->
        <div class="row" style="margin-top: 0;">
            <div class="col-md-12">
                <?php if ($this->rbac->hasPrivilege('calendar_to_do_list', 'can_view')) { ?>
                <div class="calendar-card">
                    <h4><i class="fa fa-calendar"></i> Calendar</h4>
                    <div id="calendar"></div>
                </div>
                <?php } ?>
            </div>
        </div>

    </section>
</div>

<!-- ========================================= -->
<!-- 7. SCRIPTS -->
<!-- ========================================= -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    $(function() {
        var bar_chart = "<?php echo $bar_chart ?>";
        var line_chart = "<?php echo $line_chart ?>";

        var current_month_days = <?php echo json_encode($current_month_days) ?>;
        var days_collection = <?php echo json_encode($days_collection) ?>;
        var days_expense = <?php echo json_encode($days_expense) ?>;

        var yearly_collection_array = <?php echo json_encode($yearly_collection_array) ?>;
        var total_month = <?php echo json_encode($total_month) ?>;

        var themeColor = '#6c5ce7';
        var themeColorSec = '#e17055';
        var bodyClass = $('body').attr('class');
        if(bodyClass && bodyClass.includes('theme-red')) {
            themeColor = '#e74c3c'; themeColorSec = '#c0392b';
        } else if(bodyClass && bodyClass.includes('skin-blue')) {
            themeColor = '#3498db'; themeColorSec = '#2980b9';
        } else if(bodyClass && bodyClass.includes('skin-green')) {
            themeColor = '#27ae60'; themeColorSec = '#2ecc71';
        }

        // =============================================
        // CURRENT MONTH CHART (Day Wise)
        // =============================================
        if (bar_chart) {
            var monthlyOptions = {
                series: [
                    { name: 'Fees', data: days_collection && days_collection.length ? days_collection : [] },
                    { name: 'Expenses', data: days_expense && days_expense.length ? days_expense : [] }
                ],
                chart: {
                    type: 'area',
                    height: 250,
                    toolbar: { show: false }
                },
                colors: ['#6c5ce7', '#e17055'],  // Purple for Fees, Red/Orange for Expenses
                stroke: { curve: 'smooth', width: 2 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                    }
                },
                dataLabels: { enabled: false },
                markers: { size: 0 },
                xaxis: {
                    categories: current_month_days && current_month_days.length ? current_month_days : [],
                    labels: { 
                        style: { fontSize: '10px' },
                        rotate: -45,
                        maxHeight: 50
                    }
                },
                yaxis: { 
                    labels: { 
                        style: { fontSize: '10px' },
                        formatter: function(val) { return '₹' + val; }
                    } 
                },
                grid: { borderColor: '#f1f2f6' },
                legend: { show: false },
                tooltip: { 
                    enabled: true,
                    y: { formatter: function(val) { return '₹' + val.toFixed(2); } }
                }
            };
            new ApexCharts(document.querySelector("#monthlyChart"), monthlyOptions).render();
        }

        // =============================================
        // SESSION CHART (Month Wise)
        // =============================================
        if (line_chart) {
            var sessionOptions = {
                series: [{ name: 'Collection', data: yearly_collection_array && yearly_collection_array.length ? yearly_collection_array : [] }],
                chart: {
                    type: 'area',
                    height: 250,
                    toolbar: { show: false }
                },
                colors: [themeColor],
                stroke: { curve: 'smooth', width: 2 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                    }
                },
                dataLabels: { enabled: false },
                markers: { size: 0 },
                xaxis: {
                    categories: total_month && total_month.length ? total_month : [],
                    labels: { style: { fontSize: '10px' } }
                },
                yaxis: { 
                    min: 0,
                    labels: { 
                        style: { fontSize: '10px' },
                        formatter: function(val) { 
                            return '₹' + Math.round(val);
                        }
                    } 
                },
                grid: { borderColor: '#f1f2f6' },
                tooltip: { 
                    enabled: true,
                    y: { formatter: function(val) { return '₹' + val.toFixed(2); } }
                }
            };
            new ApexCharts(document.querySelector("#sessionChart"), sessionOptions).render();
        }

        // =============================================
        // DONUT CHART
        // =============================================
        var incomeTotal = <?php echo $total_income_session; ?>;
        var expenseTotal = <?php echo $total_expenses_session; ?>;

        new ApexCharts(document.querySelector("#donutChart"), {
            series: [expenseTotal, incomeTotal],
            chart: { type: 'donut', height: 200 },
            labels: ['Expenses', 'Income'],
            colors: [themeColorSec, themeColor],
            legend: { show: false },
            plotOptions: {
                pie: { donut: { size: '62%' } }
            },
            dataLabels: { enabled: false },
            tooltip: { enabled: true }
        }).render();

        // =============================================
        // CALENDAR
        // =============================================
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            events: [
                { title: 'Fees Review', start: '2026-08-10' },
                { title: 'Staff Meeting', start: '2026-08-15' },
                { title: 'Exam Starts', start: '2026-08-20' }
            ],
            editable: false,
            eventLimit: true,
            height: 320
        });
    });

    $(document).ready(function() {
        $(document).on('click', '.close_notice', function() {
            var data = $(this).data();
            $.ajax({
                type: "POST",
                url: base_url + "admin/notification/read",
                data: {'notice': data.noticeid},
                dataType: "json",
                success: function(data) {
                    if (data.status == "fail") { errorMsg(data.msg); } else { successMsg(data.msg); }
                }
            });
        });
    });
</script>