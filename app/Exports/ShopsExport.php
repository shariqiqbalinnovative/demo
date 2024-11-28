<?php

namespace App\Exports;

use App\Models\Shop;
use Maatwebsite\Excel\Concerns\FromCollection;


use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithMapping;

class ShopsExport implements  FromQuery, WithHeadings, WithMapping, ShouldQueue
{
    /**
    * @return \Illuminate\Support\Collection
    */
    // public function collection()
    // {
    //     return Shop::all();
    // }

    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Shop::Status();

        // Apply filters based on received parameters
        if (!empty($this->filters['distributor_id'])) {
            $query->where('distributor_id', $this->filters['distributor_id']);
        }
        if (!empty($this->filters['tso_id'])) {
            $query->where('tso_id', $this->filters['tso_id']);
        }
        if (!empty($this->filters['city'])) {
            $query->where('city', $this->filters['city']);
        }
        if (!empty($this->filters['route_id'])) {
            $query->where('route_id', $this->filters['route_id']);
        }
        if (!empty($this->filters['date'])) {
            $query->whereDate('created_at', $this->filters['date']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Shop Code', 'Shop Name', 'City', 'Distributor', 'TSO', 'Route', 'Sub Route', 'Status'
        ];
    }

    public function map($shop): array
    {
        return [
            $shop->shop_code,
            $shop->company_name,
            $shop->Distributor->city ?? '--',
            $shop->distributor ? $shop->distributor->distributor_name : '',
            $shop->tso ? $shop->tso->name : '',
            $shop->route ? $shop->route->route_name : '',
            $shop->subroutes ? $shop->subroutes->route_name: '--' ,
            $this->formatStatus($shop->status) // Custom format function for status
        ];
    }

    private function formatStatus($status)
    {
        switch ($status) {
            case 1: return 'Activate';
            case 2: return 'Activate Request';
            case 3: return 'Deactivate Request';
            case 0: return 'Deactivate';
            case 4: return 'New Shop Create';
            default: return '--';
        }
    }
}
