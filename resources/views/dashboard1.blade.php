<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- jQuery + DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen pt-16">

<!-- ✅ Navigation Bar -->
<nav class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <div class="flex space-x-4">
            <a class="text-gray-700 hover:text-blue-600 font-semibold">Dashboard 1</a>
            <a class="text-gray-700 hover:text-blue-600 font-semibold">Dashboard 2</a>
            <a class="text-gray-700 hover:text-blue-600 font-semibold">Dashboard 3</a>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Logout</button>
        </form>
    </div>
</nav>

<!-- ✅ Dashboard Content -->
<div class="container mx-auto mt-10 px-4">
    <h1 class="text-3xl font-bold mb-6 text-center">Selamat datang, {{ auth()->user()->name }}!</h1>

    <!-- ✅ Tabs -->
    <div x-data="{ tab: 'tab1' }" class="mb-6" x-cloak>
        <div class="flex border-b mb-4">
            <button @click="tab = 'tab1'" :class="{ 'border-blue-500 text-blue-500': tab === 'tab1' }"
                class="px-4 py-2 border-b-2 font-semibold">Tab 1</button>
            <button @click="tab = 'tab2'" :class="{ 'border-blue-500 text-blue-500': tab === 'tab2' }"
                class="px-4 py-2 border-b-2 font-semibold">Tab 2</button>
            <button @click="tab = 'tab3'" :class="{ 'border-blue-500 text-blue-500': tab === 'tab3' }"
                class="px-4 py-2 border-b-2 font-semibold">Tab 3</button>
        </div>

        <!-- ✅ Tab Contents -->
        <template x-for="tabId in ['tab1', 'tab2', 'tab3']" :key="tabId">
            <div x-show="tab === tabId">
                <!-- Search Form Above Table -->
                <div class="bg-white p-4 rounded shadow mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" :id="`${tabId}-name`" placeholder="Name"
                            class="border px-3 py-2 rounded w-full text-sm">
                        <input type="text" :id="`${tabId}-email`" placeholder="Email"
                            class="border px-3 py-2 rounded w-full text-sm">
                    </div>
                    <div class="text-right mt-4">
                        <button :data-table="`${tabId}-table`" :data-form="tabId"
                            class="apply-search bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            Search
                        </button>
                    </div>
                </div>


                <!-- Table -->
                <table :id="`${tabId}-table`" class="display w-full bg-white rounded shadow"></table>
            </div>
        </template>
    </div>
</div>

<!-- ✅ Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<!-- ✅ DataTables Init + Manual Column Filter -->
<script>
    function initTable(tableId, useAjax = false) {
        const columns = [
            { title: "Name", data: 'name' },
            { title: "Email", data: 'email' }
        ];

        let options = {
            columns: columns,
            fixedHeader: true,
            language: {
                emptyTable: "Data kosong",
            }
        };

        if (useAjax) {
            // Server-side AJAX (Tab 1)
            options.processing = true;
            options.serverSide = true;
            options.ajax = {
                url: "{{ route('users.data') }}",
                data: function (d) {
                    console.log("Panggil db (server-side):", d);
                }
            };
            return $(`#${tableId}`).DataTable(options);
        } else {
            // Client-side fetch once via AJAX (Tab 2)
            $.ajax({
                url: "{{ route('users.all') }}", // route untuk ambil semua data
                method: "GET",
                success: function (data) {
                    options.data = data; // masukkan data ke DataTables
                    console.log("ambil data sekali (client-side)");
                    $(`#${tableId}`).DataTable(options);
                }
            });
        }
    }

    let tables = {};

    $(document).ready(function () {
        // ✅ Tab 1: Server-side
        initTable('tab1-table', true);

        // ✅ Tab 2: Client-side (load sekali)
        initTable('tab2-table'); // tidak assign ke tables karena async

        // ✅ Tab 3: Kosong
        tables['tab3-table'] = $('#tab3-table').DataTable({
            data: [],
            columns: [
                { title: "Name", data: 'name' },
                { title: "Email", data: 'email' }
            ],
            language: {
                emptyTable: "Data kosong"
            }
        });

        // 🔍 Handle tombol Search
        $(document).on('click', '.apply-search', function () {
            const tableId = $(this).data('table');
            const formId = $(this).data('form');

            const name = $(`#${formId}-name`).val();
            const email = $(`#${formId}-email`).val();

            const table = $(`#${tableId}`).DataTable();
            table.column(0).search(name);
            table.column(1).search(email);
            table.draw();
        });
    });
</script>


</body>
</html>
