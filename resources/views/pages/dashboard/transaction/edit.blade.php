<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a class="hover:underline" href="{{route('dashboard.transaction.index')}}">Transaction</a> &raquo; <a class="hover:underline" href="{{route('dashboard.transaction.show', $item->id)}}">#{{ $item->id }}</a> &raquo; Edit
        </h2>
    </x-slot>
    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="font-semibold text-lg text-gray-800 leading-tight mb-4">Transaction Details</h2>
            <div class="flex bg-white overflow-hidden shadow sm:rounded-lg mb-4">
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
                    <div class="py-6 pl-6 pr-2 bg-white border-b border-gray-200 w-1/2">
                    <form class="w-full" action="{{ route('dashboard.transaction.update', ['transaction' => $item->id, 'previous_page' => $previous_page]) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <table class="table-auto w-full">
                            <tbody>
                                <tr>
                                    <th class="border px-6 py-2 text-right ">Name</th>
                                    <td class="border px-6 py-2">{{ $item->user->name }}</td>
                                </tr>
                                <tr>
                                    <th class="border px-6 py-2 text-right">Email</th>
                                    <td class="border px-6 py-2">{{ $item->user->email }}</td>
                                </tr>
                                <tr>
                                    <th class="border px-6 py-2 text-right">Address</th>
                                    <td class="border px-6 py-2">{{ $item->address }}</td>
                                </tr>
                                <tr>
                                    <th class="border px-6 py-2 text-right">Total Price</th>
                                    <td class="border px-6 py-2">{{ number_format($item->total_price) }}</td>
                                </tr>
                                <tr>
                                    <th class="border px-6 py-2 text-right">Shipping Price</th>
                                    <td class="border px-6 py-2">{{ number_format($item->shipping_price) }}</td>
                                </tr>
                                <tr>
                                    <th class="border px-6 py-2 text-right">
                                        <label class="block uppercase tracking-wide text-gray-700 text-l font-bold" for="grid-last-name">
                                            transaction_type
                                        </label>
                                    </th>
                                    <td class="border px-6 py-2">
                                        <select name="transaction_type" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-2 px-2 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name">
                                            <option value="{{ $item->transaction_type }}">{{ $item->transaction_type }}</option>
                                            <option disabled>-------</option>
                                            <option value="dine_in">dine_in</option>
                                            <option value="takeaway">takeaway</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="border px-6 py-2 text-right">
                                        <label class="block uppercase tracking-wide text-gray-700 text-l font-bold " for="grid-last-name">
                                            Payment
                                        </label>
                                    </th>
                                    <td class="border px-6 py-2">
                                        <select name="payment" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-2 px-2 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name">
                                            <option value="{{ $item->payment }}">{{ $item->payment }}</option>
                                            <option disabled>-------</option>
                                            <option value="QRIS">QRIS</option>
                                            <option value="CASH">CASH</option>
                                        </select></td>
                                </tr>
                                <tr>
                                    <th class="border px-6 py-2 text-right">
                                        <label class="block uppercase tracking-wide text-gray-700 text-l font-bold" for="grid-last-name">
                                            payment_type
                                        </label>
                                    </th>
                                    <td class="border px-6 py-2">
                                        <select name="payment_type" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-2 px-2 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name">
                                            <option value="{{ $item->payment_type }}">{{ $item->payment_type }}</option>
                                            <option disabled>-------</option>
                                            <option value="bayar_sekarang">bayar_sekarang</option>
                                            <option value="bayar_nanti">bayar_nanti</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="border px-6 py-2 text-right"><label class="block uppercase tracking-wide text-gray-700 text-l font-bold" for="grid-last-name">
                                        Status Payment
                                    </label></th>
                                    <td class="border px-6 py-2">
                                    <select name="status_payment" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-2 px-2 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name">
                                        <option value="{{ $item->status_payment }}">{{ $item->status_payment }}</option>
                                        <option disabled>-------</option>
                                        <option value="UNPAID">UNPAID</option>
                                        <option value="REVIEW">REVIEW</option>
                                        <option value="PAID">PAID</option>
                                        <option value="REJECTED">REJECTED</option>
                                        <option value="EXPIRED">EXPIRED</option>
                                    </select></td>
                                </tr>
                                <tr>
                                    <th class="border px-6 py-2 text-right">
                                        <label class="block uppercase tracking-wide text-gray-700 text-l font-bold" for="grid-last-name">
                                            Status
                                        </label>
                                    </th>
                                    <td class="border px-6 py-2">
                                        <select name="status" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-2 px-2 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name">
                                            <option value="{{ $item->status }}">{{ $item->status }}</option>
                                            <option disabled>-------</option>
                                            <option value="PENDING">PENDING</option>
                                            <option value="PROCESS">PROCESS</option>
                                            <option value="READY">READY</option>
                                            <option value="SUCCESS">SUCCESS</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="py-6 pr-6 pl-2 bg-white border-b border-gray-200 w-1/2">
                        <div class="flex flex-wrap -mx-3 mb-6 max-h-full">
                            <div class="w-full px-3">
                                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                                    Payment Image
                                </label>
                                <div class="grid grid-cols-7  place-items-center">
                                    <span class="col-span-3"><p class="text-center lowercase tracking-wide text-gray-700 text-xs font-bold mb-4">Currect Picture</p></span>
                                    <span></span>
                                    <span class="col-span-3"><p class="text-center lowercase tracking-wide text-gray-700 text-xs font-bold mb-4">Updated Picture</p></span>
                                </div>
                                    <div class="grid grid-cols-7  place-items-center">
                                        <span class="col-span-3">
                                            <img src="{{$item->payment_image ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Empty_set_symbol.svg/640px-Empty_set_symbol.svg.png'}}"/>
                                        </span>
                                        <span class="font-semibold text-3xl text-center ">
                                            >>
                                        </span>
                                        <span class="col-span-3">
                                        <img src="{{$item->payment_image ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Empty_set_symbol.svg/640px-Empty_set_symbol.svg.png'}}" id="payment_image_preview" />
                                    </div>
                                <div class="py-4">
                                    <input accept="image/*" value="{{ old('payment_image') ?? $item->payment_image }}" name="payment_image" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="payment_image_input" type="file" placeholder="Payment Image">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="flex flex-wrap -mx-3 mt-8 mb-6">
                    <div class="w-full px-3 text-right">
                        <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Update Transaction
                        </button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.ckeditor.com/4.16.0/standard/ckeditor.js"></script>
    <script>
        CKEDITOR.replace('description');
            // Use the function for profile photo and QRIS uploads
            handleImageUpload('payment_image_input', 'payment_image_preview',);
    </script>
</x-app-layout>
