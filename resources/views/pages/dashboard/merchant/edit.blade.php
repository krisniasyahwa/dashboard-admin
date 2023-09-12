<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Category &raquo; {{ $item->name }} &raquo; Edit
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div>
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
                <form class="w-full" action="{{ route('dashboard.merchant.update', $item->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <div class="flex flex-wrap -mx-3 mb-6">
                        <div class="w-full px-3">
                            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                                Name
                            </label>
                            <input value="{{ old('name') ?? $item->name }}" name="name" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name" type="text" placeholder="Merchant Name">
                        </div>
                    </div>

                    <div class="flex flex-wrap -mx-3 mb-6">
                        <div class="w-full px-3">
                            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                                Slug
                            </label>
                            <input value="{{ old('name') ?? $item->slug }}" name="slug" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name" type="text" placeholder="Merchant Slug">
                        </div>
                    </div>

                    <div class="flex flex-wrap -mx-3 mb-6">
                        <div class="w-full px-3">
                            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                                Concurrent Transaction
                            </label>
                            <input value="{{ old('concurrent_transaction') ?? $item->concurrent_transaction}}" name="concurrent_transaction" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name" type="text" placeholder="Merchant Concurrent Transaction">
                        </div>
                    </div>

                    <div class="flex flex-wrap -mx-3 mb-6">
                        <div class="w-full px-3">
                            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                                Address
                            </label>
                            <input value="{{ old('address') ?? $item->address }}" name="address" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name" type="text" placeholder="Merchant Address">
                        </div>
                    </div>

                    <div class="flex flex-wrap -mx-3 mb-6">
                        <div class="w-full px-3">
                            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                                Phone
                            </label>
                            <input value="{{ old('phone') ?? $item->phone }}" name="phone" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name" type="text" placeholder="Merchant Phone">
                        </div>
                    </div>

                    <div class="flex flex-wrap -mx-3 mb-6">
                        <div class="w-full px-3">
                            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                                Photo Profile
                            </label>
                            <div class="grid grid-cols-3  place-items-center w-1/2">
                                <span><p class="text-center lowercase tracking-wide text-gray-700 text-xs font-bold mb-4">Currect Picture</p></span>
                                <span></span>
                                <span><p class="text-center lowercase tracking-wide text-gray-700 text-xs font-bold mb-4">Updated Picture</p></span>
                            </div>
                        <div class="grid grid-cols-3  place-items-center w-1/2">
                            <span>
                                <img src="{{$item->profile_photo_path ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Empty_set_symbol.svg/640px-Empty_set_symbol.svg.png'}}" class="rounded-full w-40 h-40 mb-4"/>
                            </span>
                            <span class="font-semibold text-3xl text-center ">
                                >>
                            </span>
                            <span>
                            <img src="{{$item->profile_photo_path ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Empty_set_symbol.svg/640px-Empty_set_symbol.svg.png'}}" id="profile-photo-preview" class="rounded-full w-40 h-40 mb-4"/>
                        </div>
                        <input accept="image/*" value="{{ old('profile_photo_path') ?? $item->profile_photo_path }}" name="profile_photo_path" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="profile-photo-input" type="file" placeholder="Merchant Photo Profile">
                        </div>
                    </div>

                    <div class="flex flex-wrap -mx-3 mb-6">
                        <div class="w-full px-3">
                            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
                                Photo Profile
                            </label>
                            <div class="grid grid-cols-3  place-items-center w-1/2">
                                <span><p class="text-center lowercase tracking-wide text-gray-700 text-xs font-bold mb-4">Currect Picture</p></span>
                                <span></span>
                                <span><p class="text-center lowercase tracking-wide text-gray-700 text-xs font-bold mb-4">Updated Picture</p></span>
                            </div>
                        <div class="grid grid-cols-3  place-items-center w-1/2">
                            <span>
                                <img src="{{$item->qris_path ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Empty_set_symbol.svg/640px-Empty_set_symbol.svg.png'}}" class="w-80 mb-4"/>
                            </span>
                            <span class="font-semibold text-3xl text-center ">
                                >>
                            </span>
                            <span>
                            <img src="{{$item->qris_path ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Empty_set_symbol.svg/640px-Empty_set_symbol.svg.png'}}" id="qris-preview" class="w-80 mb-4"/>
                        </div>
                        <input accept="image/*" value="{{ old('qris_path') ?? $item->qris_path }}" name="qris_path" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="qris-input" type="file" placeholder="Merchant Photo Profile">
                        </div>
                    </div>

                    <div class="flex flex-wrap -mx-3 mb-6">
                        <div class="w-full px-3 text-right">
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Update Merchant
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <x-slot name="script">
        <script>
             // Use the function for profile photo and QRIS uploads
            handleImageUpload('profile-photo-input', 'profile-photo-preview',);
            handleImageUpload('qris-input', 'qris-preview');
        </script>
    </x-slot>
</x-app-layout>
