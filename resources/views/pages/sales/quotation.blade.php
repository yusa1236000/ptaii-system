@extends('layouts.app')
@section('content')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-semibold">Quotations</h1>
            <div class="flex space-x-2">
                <button class="bg-blue-500 text-white px-4 py-2 rounded">Create</button>
                <button class="bg-gray-300 text-black px-4 py-2 rounded">Import</button>
            </div>
        </div>
        <div class="flex justify-between items-center mb-4">
            <input type="text" placeholder="Search..." class="border border-gray-300 rounded px-4 py-2 w-1/3">
            <div class="flex items-center space-x-2">
                <span>1-80 / 17062</span>
                <button class="p-2"><i class="fas fa-th"></i></button>
                <button class="p-2"><i class="fas fa-list"></i></button>
            </div>
        </div>
        <div class="overflow-x-auto bg-white rounded shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quotation Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commitment Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salesperson</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">1</td>
                        <td class="px-6 py-4 whitespace-nowrap">ZI-ARM.2025.03.12</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">ARMSTRONG INDUSTRI INDONESIA, PT</td>
                        <td class="px-6 py-4 whitespace-nowrap">Herdi</td>
                        <td class="px-6 py-4 whitespace-nowrap">Rp 16,512,595.00000</td>
                        <td class="px-6 py-4 whitespace-nowrap">Quotation</td>
                    </tr>
                    <tr class="bg-gray-100">
                        <td class="px-6 py-4 whitespace-nowrap">2</td>
                        <td class="px-6 py-4 whitespace-nowrap">ZI-NKR.ARM.2025.03.15</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">ARMSTRONG INDUSTRI INDONESIA, PT</td>
                        <td class="px-6 py-4 whitespace-nowrap">Herdi</td>
                        <td class="px-6 py-4 whitespace-nowrap">Rp 11,079,078.72000</td>
                        <td class="px-6 py-4 whitespace-nowrap">Quotation</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">3</td>
                        <td class="px-6 py-4 whitespace-nowrap">ZI-PBG.2025.03.02</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">INDO PATRIA GLOBAL, PT</td>
                        <td class="px-6 py-4 whitespace-nowrap">Herdi</td>
                        <td class="px-6 py-4 whitespace-nowrap">Rp 51,806,919.00000</td>
                        <td class="px-6 py-4 whitespace-nowrap">Sales Order</td>
                    </tr>
                    <tr class="bg-gray-100">
                        <td class="px-6 py-4 whitespace-nowrap">4</td>
                        <td class="px-6 py-4 whitespace-nowrap">ZI-PB.2025.03.01</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">PT. PARAMOUNT BED INDONESIA</td>
                        <td class="px-6 py-4 whitespace-nowrap">Herdi</td>
                        <td class="px-6 py-4 whitespace-nowrap">Rp 34,593,364.23000</td>
                        <td class="px-6 py-4 whitespace-nowrap">Sales Order</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">5</td>
                        <td class="px-6 py-4 whitespace-nowrap">ZI-YEMI.2025.03.02</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">PT. YAMAHA ELECTRONICS MANUFACTURING INDONESIA</td>
                        <td class="px-6 py-4 whitespace-nowrap">Herdi</td>
                        <td class="px-6 py-4 whitespace-nowrap">$ 387.059100</td>
                        <td class="px-6 py-4 whitespace-nowrap">Sales Order</td>
                    </tr>
                    <tr class="bg-gray-100">
                        <td class="px-6 py-4 whitespace-nowrap">6</td>
                        <td class="px-6 py-4 whitespace-nowrap">ZI-NKR.ARM.2025.03.14</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 17:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 16:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">03/12/2025 16:00:00</td>
                        <td class="px-6 py-4 whitespace-nowrap">ARMSTRONG INDUSTRI INDONESIA, PT</td>
                        <td class="px-6 py-4 whitespace-nowrap">Herdi</td>
                        <td class="px-6 py-4 whitespace-nowrap">Rp 2,486,400.00000</td>
                        <td class="px-6 py-4 whitespace-nowrap">Quotation</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
@endsection