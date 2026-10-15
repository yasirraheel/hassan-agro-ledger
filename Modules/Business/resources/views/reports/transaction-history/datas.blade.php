@foreach($transcations as $transcation)
    <tr>
        <td>{{ ($transcations->currentPage() - 1) * $transcations->perPage() + $loop->iteration }}</td>
        <td class="text-start">{{ $transcation->invoiceNumber }}</td>
        <td class="text-start">{{ $transcation->party?->name }}</td>
        <td class="text-start">{{ currency_format($transcation->totalDue, 'icon', 2, business_currency()) }}</td>
        <td class="text-start">{{ currency_format($transcation->payDueAmount, 'icon', 2, business_currency()) }}</td>
        <td class="text-start">{{ $transcation->paymentType }}</td>
        <td class="text-start">{{ formatted_date($transcation->paymentDate) }}</td>
    </tr>
@endforeach
