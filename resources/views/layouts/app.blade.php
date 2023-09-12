<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        <link href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.dataTables.min.css" rel="stylesheet">
        <style>
            /*Form fields*/
            .dataTables_wrapper select,
            .dataTables_wrapper .dataTables_filter input {
                color: #4a5568; 			/*text-gray-700*/
                padding-left: 1rem; 		/*pl-4*/
                padding-right: 1rem; 		/*pl-4*/
                padding-top: .5rem; 		/*pl-2*/
                padding-bottom: .5rem; 		/*pl-2*/
                line-height: 1.25; 			/*leading-tight*/
                border-width: 2px; 			/*border-2*/
                border-radius: .25rem;
                border-color: #edf2f7; 		/*border-gray-200*/
                background-color: #edf2f7; 	/*bg-gray-200*/
            }

            /*Row Hover*/
            table.dataTable.hover tbody tr:hover, table.dataTable.display tbody tr:hover {
                background-color: #ebf4ff;	/*bg-indigo-100*/
            }

            /*Pagination Buttons*/
            .dataTables_wrapper .dataTables_paginate .paginate_button		{
                font-weight: 700;				/*font-bold*/
                border-radius: .25rem;			/*rounded*/
                border: 1px solid transparent;	/*border border-transparent*/
            }

            /*Pagination Buttons - Current selected */
            .dataTables_wrapper .dataTables_paginate .paginate_button.current	{
                color: #fff !important;				/*text-white*/
                box-shadow: 0 1px 3px 0 rgba(0,0,0,.1), 0 1px 2px 0 rgba(0,0,0,.06); 	/*shadow*/
                font-weight: 700;					/*font-bold*/
                border-radius: .25rem;				/*rounded*/
                background: #667eea !important;		/*bg-indigo-500*/
                border: 1px solid transparent;		/*border border-transparent*/
            }

            /*Pagination Buttons - Hover */
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover		{
                color: #fff !important;				/*text-white*/
                box-shadow: 0 1px 3px 0 rgba(0,0,0,.1), 0 1px 2px 0 rgba(0,0,0,.06);	 /*shadow*/
                font-weight: 700;					/*font-bold*/
                border-radius: .25rem;				/*rounded*/
                background: #667eea !important;		/*bg-indigo-500*/
                border: 1px solid transparent;		/*border border-transparent*/
            }

            /*Add padding to bottom border */
            table.dataTable.no-footer {
                border-bottom: 1px solid #e2e8f0;	/*border-b-1 border-gray-300*/
                margin-top: 0.75em;
                margin-bottom: 0.75em;
            }

            /*Change colour of responsive icon*/
            table.dataTable.dtr-inline.collapsed>tbody>tr>td:first-child:before, table.dataTable.dtr-inline.collapsed>tbody>tr>th:first-child:before {
                background-color: #667eea !important; /*bg-indigo-500*/
            }

            /*Change all data table head text align to left*/
            table.dataTable thead {
                text-align: left
            }
            /* Change all data table length dropdown  */
            select[name="crudTable_length"] {
                width: 5rem;
            }
            /* change search datatable length */
            .dataTables_filter, {
                width: 40rem;
            }
            .dataTables_filter input{
                width: 35rem;
            }

            /* change datatable body padding */
            table.dataTable tbody th,table.dataTable tbody td {
                padding: 4px 6px
            }

            /* change datatable th padding */
            table.dataTable thead th,table.dataTable thead td {
                padding: 8px 14px;
                border-bottom: 1px solid #111
            }
        </style>

        @livewireStyles

        <!-- Scripts -->
        <script src="{{ mix('js/app.js') }}" defer></script>
        <script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
        <!-- Customs script -->
        <script>
            function getParameterByName(name, url) {
                if (!url) url = window.location.href;
                name = name.replace(/[\[\]]/g, "\\$&");
                var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
                    results = regex.exec(url);
                if (!results) return null;
                if (!results[2]) return '';
                return decodeURIComponent(results[2].replace(/\+/g, " "));
            }
            function handleImageUpload(inputId, previewId) {
                // Get references to the input and img elements
                const inputElement = document.getElementById(inputId);
                const previewElement = document.getElementById(previewId);
                const defaultSrc = previewElement.src
                let objectURL = null; // Initialize objectURL

                // Add an event listener to the input element
                inputElement.addEventListener('change', (event) => {
                    // Check if a file has been selected
                    if (event.target.files.length > 0) {
                    // Get the selected file
                    const selectedFile = event.target.files[0];

                    // Create a FileReader to read the file
                    const reader = new FileReader();

                    // Set up a function to run when the FileReader has loaded the file
                    reader.onload = (e) => {
                        // Revoke the previous object URL if it exists
                        if (objectURL) {
                        URL.revokeObjectURL(objectURL);
                        }

                        // Update the src attribute of the img element with the selected image data
                        previewElement.src = e.target.result;

                        // Create a new object URL for the selected file
                        objectURL = URL.createObjectURL(selectedFile);
                    };

                    // Read the selected file as a data URL
                    reader.readAsDataURL(selectedFile);
                    } else {
                    // No file selected, return the default img
                    previewElement.src = defaultSrc

                    if (objectURL) {
                        URL.revokeObjectURL(objectURL);
                        objectURL = null; // Reset objectURL
                    }
                    }
                });
                }
        </script>
    </head>
    <body class="font-sans antialiased">
        <x-jet-banner />

        <div class="min-h-screen bg-gray-100">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts

        {{ $script ?? '' }}
    </body>
</html>
