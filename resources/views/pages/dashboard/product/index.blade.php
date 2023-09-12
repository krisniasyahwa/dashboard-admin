<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Product') }}
        </h2>
    </x-slot>

    <x-slot name="script">
        <script>
            // AJAX DataTable
            var datatable = $('#crudTable').DataTable({
                ajax: {
                    url: '{!! url()->current() !!}',
                },
                columns: [
                    { data: 'id', name: 'id', width: '5%'},
                    { data: 'name', name: 'name' },
                    { data: 'stock', name: 'stock' },
                    { data: 'price', name: 'price' },
                    { data: 'promo_price', name: 'promo_price' },
                    { data: 'takeway_charge', name: 'takeway_charge' },
                    { data: 'best_seller', name: 'best_seller' },
                    { data: 'category.name', name: 'category.name' },
                    { data: 'merchant.name', name: 'merchant.name' },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        width: '25%'
                    },
                ],
            });

            // enable searching feature from search params url
            var searchValue = getParameterByName('search') != null ? getParameterByName('search') : "";
            datatable.search(searchValue).draw();
        </script>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-10">
                <a href="{{ route('dashboard.product.create') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow-lg">
                    + Create Product
                </a>
            </div>
            <div class="shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 bg-white sm:p-6">
                    <table id="crudTable">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Stock</th>
                            <th>Harga</th>
                            <th>Promo</th>
                            <th>Takeway</th>
                            <th>Best Seller</th>
                            <th>Kategori</th>
                            <th>Merchant</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
