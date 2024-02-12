<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Stock Rollback') }}
        </h2>
    </x-slot>


    <x-slot name="script">
        @php
            // get rollbackid from parameter
            $params = request()->query();
            $rollbackedIds = isset($params['rollbackedIds']) ? $params['rollbackedIds'] : null;
            echo json_encode($rollbackedIds) . "\n";
            if ($rollbackedIds !== null) {
                if (strpos($rollbackedIds, '&') !== false) {
                    $ids = array_map(function ($id) {
                        [$key, $val] = explode('=', $id);
                        return (int) $val;
                    }, explode('&', $rollbackedIds));
                    echo 'here 21' . "\n";
                } else {
                    [$_, $ids] = explode('=', $rollbackedIds);
                    echo 'here 24' . "$ids\n";
                }
            } else {
                echo 'here 27' . "\n";
                $ids = [];
            }
        @endphp
        {{-- ? moments js --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
        <script>
            const ids = @json($ids);;
            // AJAX DataTable
            var datatable = $('#crudTable').DataTable({
                ajax: {
                    url: '{!! url()->current() !!}',
                },
                columns: [{
                        data: 'id',
                        name: 'id',
                        width: '5%'
                    },
                    {
                        data: 'stock_rollback_at',
                        name: 'Stock Rollback',
                        render: function(data) {
                            return data != null ?
                                `<span> ✅@ ${
                        moment(data).format('MMMM Do YYYY, h:mm:ss a')
                    } </span>` :
                                `<span>❌</span>`;
                        },
                        width: '20%'
                    }, {
                        data: 'merchant.name',
                        name: 'merchant.name',
                    }, {
                        data: 'user.name',
                        name: 'user.name',
                    }, {
                        data: 'status_payment',
                        name: 'status_payment',
                    }, {
                        data: 'created_at',
                        name: 'created_at',
                        render: (data) => moment(data).format('MMMM Do YYYY, h:mm:ss a'),
                    }, {
                        data: 'updated_at',
                        name: 'updated_at',
                        render: (data) => moment(data).format('MMMM Do YYYY, h:mm:ss a'),
                    }, {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        width: '15%',

                    },
                ],
                order: [
                    [1, 'desc']
                ],
                "rowCallback": function(row, data) {
                    // add background color for rollbacked id to bg-lime-300/0.7 and hover:bg-lime-300
                    if (ids.includes(data.id)) {
                        $(row).css('background-color', 'rgb(190, 242, 100, 0.7)');

                        $(row).mouseover(function() {
                            return $(row).css("background-color", "rgb(190, 242, 100, 1)")
                        });
                        $(row).mouseout(function() {
                            return $(row).css('background-color', 'rgb(190, 242, 100, 0.7)')
                        });
                    }
                    // add hover:bg-slate-200
                    else {
                        $(row).mouseover(function() {
                            return $(row).css("background-color", "#e2e8f0")
                        });
                        $(row).mouseout(function() {
                            return $(row).css('background-color', 'white')
                        });
                    }
                }
            });
        </script>
    </x-slot>
    <div class="hover:bg-green-800"></div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-5" role="alert">
                    <div class="bg-red-500 text-white font-bold rounded-t px-4 py-2">
                        There's something wrong!
                    </div>
                    <div class="border border-t-0 border-red-400 rounded-b bg-red-100 px-4 py-3 text-red-700">
                        <p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        </p>
                    </div>
                </div>
            @endif
            <h2 class="p-4 bg-yellow-200 text-red-500 my-4 font-bold rounded-xl">⚠️ Showing Rejected & Expired
                transaction only
                {{ $data['needs_rollbacked'] > 0 ? '   ||   ( ' . $data['needs_rollbacked'] . '  Transactions needs to be rollback! )' : '' }}
            </h2>
            <div class="mb-4 flex justify-end">
                <form class="inline-block" action="{{ route('dashboard.stock.rollback.all') }}" method="POST">
                    <button
                        class="border border-blue-700 bg-blue-700 text-white rounded-md px-4 py-2 transition duration-500 ease select-none hover:bg-blue-800 focus:outline-none focus:shadow-outline">
                        Rollback All
                    </button>
                    {{ method_field('PUT') }}
                    {{ csrf_field() }}
                </form>
                {{-- <a href="{{ route('dashboard.merchant.create') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow-lg">
                    + Create Merchant
                </a> --}}
            </div>
            <div class="shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 bg-white sm:p-6">
                    <table id="crudTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Stock Rollback</th>
                                <th>Merchant</th>
                                <th>User</th>
                                <th>Status Payment</th>
                                <th>Created_at</th>
                                <th>Updated_at</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <pre>
        </pre>
    </div>
</x-app-layout>
