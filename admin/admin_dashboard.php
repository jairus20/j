<?php include 'admin_header.php'; ?>

<main id="main-content">
    <!-- Dashboard Content -->
    <div id="dashboard-content">
        <div class="head-title">
            <div class="left">
                <h1>Dashboard</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Home</a></li>
                </ul>
            </div>
            <a href="#" class="btn-download">
                <i class='bx bxs-cloud-download bx-fade-down-hover'></i>
                <span class="text">Get PDF</span>
            </a>
        </div>

        <ul class="box-info">
            <li>
                <i class='bx bxs-calendar-check'></i>
                <span class="text">
                    <h3>1020</h3>
                    <p>New Order</p>
                </span>
            </li>
            <li>
                <i class='bx bxs-group'></i>
                <span class="text">
                    <h3>2834</h3>
                    <p>Visitors</p>
                </span>
            </li>
            <li>
                <i class='bx bxs-dollar-circle'></i>
                <span class="text">
                    <h3>N$2543.00</h3>
                    <p>Total Sales</p>
                </span>
            </li>
        </ul>

        <div class="table-data">
            <div class="order">
                <div class="head">
                    <h3>Recent Orders</h3>
                    <i class='bx bx-search'></i>
                    <i class='bx bx-filter'></i>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Date Order</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sample rows -->
                        <tr>
                            <td>
                                <img src="https://placehold.co/600x400/png" alt="User">
                                <p>Micheal John</p>
                            </td>
                            <td>18-10-2021</td>
                            <td><span class="status completed">Completed</span></td>
                        </tr>
                        <!-- ... other rows ... -->
                    </tbody>
                </table>
            </div>
            <div class="todo">
                <div class="head">
                    <h3>Todos</h3>
                    <i class='bx bx-plus icon'></i>
                    <i class='bx bx-filter'></i>
                </div>
                <ul class="todo-list">
                    <li class="completed">
                        <p>Check Inventory</p>
                        <i class='bx bx-dots-vertical-rounded'></i>
                    </li>
                    <!-- ... other todos ... -->
                </ul>
            </div>
        </div>
    </div>
    <!-- End Dashboard Content -->
</main>

<?php include 'admin_footer.php'; ?>