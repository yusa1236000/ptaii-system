<?php

namespace App\Http\Controllers\Api\Sales;

use App\Models\SalesForecast;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesForecastController extends Controller
{
    /**
     * Display a listing of the sales forecasts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $forecasts = SalesForecast::with(['customer', 'item'])->get();
        return response()->json(['data' => $forecasts], 200);
    }

    /**
     * Store a newly created sales forecast in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:Item,item_id',
            'customer_id' => 'required|exists:Customer,customer_id',
            'forecast_period' => 'required|date',
            'forecast_quantity' => 'required|numeric|min:0',
            'actual_quantity' => 'nullable|numeric|min:0',
            'variance' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate variance if both forecast and actual quantities are provided
        $data = $request->all();
        if ($request->has('forecast_quantity') && $request->has('actual_quantity')) {
            $data['variance'] = $request->actual_quantity - $request->forecast_quantity;
        }

        $forecast = SalesForecast::create($data);

        return response()->json(['data' => $forecast, 'message' => 'Sales forecast created successfully'], 201);
    }

    /**
     * Display the specified sales forecast.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $forecast = SalesForecast::with(['customer', 'item'])->find($id);
        
        if (!$forecast) {
            return response()->json(['message' => 'Sales forecast not found'], 404);
        }
        
        return response()->json(['data' => $forecast], 200);
    }

    /**
     * Update the specified sales forecast in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $forecast = SalesForecast::find($id);
        
        if (!$forecast) {
            return response()->json(['message' => 'Sales forecast not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:Item,item_id',
            'customer_id' => 'required|exists:Customer,customer_id',
            'forecast_period' => 'required|date',
            'forecast_quantity' => 'required|numeric|min:0',
            'actual_quantity' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate variance if both forecast and actual quantities are provided
        $data = $request->all();
        if ($request->has('forecast_quantity') && $request->has('actual_quantity')) {
            $data['variance'] = $request->actual_quantity - $request->forecast_quantity;
        }

        $forecast->update($data);
        return response()->json(['data' => $forecast, 'message' => 'Sales forecast updated successfully'], 200);
    }

    /**
     * Remove the specified sales forecast from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $forecast = SalesForecast::find($id);
        
        if (!$forecast) {
            return response()->json(['message' => 'Sales forecast not found'], 404);
        }
        
        $forecast->delete();
        return response()->json(['message' => 'Sales forecast deleted successfully'], 200);
    }

    /**
     * Generate sales forecasts based on historical data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateForecasts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_period' => 'required|date',
            'end_period' => 'required|date|after:start_period',
            'customer_id' => 'nullable|exists:Customer,customer_id',
            'item_id' => 'nullable|exists:Item,item_id',
            'method' => 'required|in:average,weighted,trend'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get historical sales data
            $query = SalesInvoiceLine::join('SalesInvoice', 'SalesInvoiceLine.invoice_id', '=', 'SalesInvoice.invoice_id')
                ->where('SalesInvoice.status', 'Paid')
                ->select(
                    'SalesInvoiceLine.item_id',
                    'SalesInvoice.customer_id',
                    DB::raw('DATE_FORMAT(SalesInvoice.invoice_date, "%Y-%m-01") as period'),
                    DB::raw('SUM(SalesInvoiceLine.quantity) as total_quantity')
                )
                ->groupBy('SalesInvoiceLine.item_id', 'SalesInvoice.customer_id', 'period');

            // Apply filters if provided
            if ($request->has('customer_id')) {
                $query->where('SalesInvoice.customer_id', $request->customer_id);
            }

            if ($request->has('item_id')) {
                $query->where('SalesInvoiceLine.item_id', $request->item_id);
            }

            $historicalData = $query->get();

            // Group data by item and customer
            $groupedData = [];
            foreach ($historicalData as $record) {
                $key = $record->item_id . '-' . $record->customer_id;
                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = [
                        'item_id' => $record->item_id,
                        'customer_id' => $record->customer_id,
                        'periods' => []
                    ];
                }
                $groupedData[$key]['periods'][$record->period] = $record->total_quantity;
            }

            $forecasts = [];

            // Generate forecasts for each item-customer pair
            foreach ($groupedData as $data) {
                $periods = $data['periods'];
                
                // Get start and end dates for forecasting
                $startDate = new \DateTime($request->start_period);
                $endDate = new \DateTime($request->end_period);
                $interval = new \DateInterval('P1M'); // 1 month interval
                
                $forecastPeriod = $startDate;
                
                while ($forecastPeriod <= $endDate) {
                    $periodKey = $forecastPeriod->format('Y-m-01');
                    
                    // Skip if there's already an existing forecast
                    $existingForecast = SalesForecast::where('item_id', $data['item_id'])
                        ->where('customer_id', $data['customer_id'])
                        ->where('forecast_period', $periodKey)
                        ->first();
                    
                    if (!$existingForecast) {
                        // Calculate forecast based on the selected method
                        $forecastQuantity = 0;
                        
                        switch ($request->method) {
                            case 'average':
                                // Simple average of historical data
                                if (count($periods) > 0) {
                                    $forecastQuantity = array_sum($periods) / count($periods);
                                }
                                break;
                                
                            case 'weighted':
                                // Weighted average with more recent months having higher weights
                                $totalWeight = 0;
                                $weightedSum = 0;
                                $weight = 1;
                                
                                // Sort periods by date (oldest first)
                                ksort($periods);
                                
                                foreach ($periods as $period => $quantity) {
                                    $weightedSum += $quantity * $weight;
                                    $totalWeight += $weight;
                                    $weight++; // Increase weight for more recent periods
                                }
                                
                                if ($totalWeight > 0) {
                                    $forecastQuantity = $weightedSum / $totalWeight;
                                }
                                break;
                                
                            case 'trend':
                                // Linear trend based on historical data
                                if (count($periods) >= 2) {
                                    // Sort periods by date
                                    ksort($periods);
                                    
                                    $x = [];
                                    $y = [];
                                    $i = 1;
                                    
                                    foreach ($periods as $period => $quantity) {
                                        $x[] = $i;
                                        $y[] = $quantity;
                                        $i++;
                                    }
                                    
                                    // Calculate linear regression
                                    $n = count($x);
                                    $sumX = array_sum($x);
                                    $sumY = array_sum($y);
                                    $sumXY = 0;
                                    $sumXX = 0;
                                    
                                    for ($j = 0; $j < $n; $j++) {
                                        $sumXY += ($x[$j] * $y[$j]);
                                        $sumXX += ($x[$j] * $x[$j]);
                                    }
                                    
                                    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
                                    $intercept = ($sumY - $slope * $sumX) / $n;
                                    
                                    // Predict next value
                                    $forecastQuantity = $intercept + $slope * ($n + 1);
                                }
                                break;
                        }
                        
                        // Ensure forecast quantity is not negative
                        $forecastQuantity = max(0, $forecastQuantity);
                        
                        $forecast = SalesForecast::create([
                            'item_id' => $data['item_id'],
                            'customer_id' => $data['customer_id'],
                            'forecast_period' => $periodKey,
                            'forecast_quantity' => round($forecastQuantity, 2),
                            'actual_quantity' => null,
                            'variance' => null
                        ]);
                        
                        $forecasts[] = $forecast;
                    }
                    
                    // Move to next period
                    $forecastPeriod->add($interval);
                }
            }

            DB::commit();
            
            return response()->json([
                'data' => $forecasts, 
                'message' => count($forecasts) . ' forecasts generated successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate forecasts', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update actual quantities in forecasts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateActuals(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'end_period' => 'required|date',
            'update_all' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get completed period forecasts
            $query = SalesForecast::where('forecast_period', '<=', $request->end_period);
            
            if (!$request->update_all) {
                // Only update forecasts with null actual_quantity
                $query->whereNull('actual_quantity');
            }
            
            $forecasts = $query->get();
            $updatedCount = 0;
            
            foreach ($forecasts as $forecast) {
                // Get actual sales quantities for the period
                $startOfMonth = new \DateTime($forecast->forecast_period);
                $endOfMonth = clone $startOfMonth;
                $endOfMonth->modify('last day of this month');
                
                $actualQuantity = SalesInvoiceLine::join('SalesInvoice', 'SalesInvoiceLine.invoice_id', '=', 'SalesInvoice.invoice_id')
                    ->where('SalesInvoiceLine.item_id', $forecast->item_id)
                    ->where('SalesInvoice.customer_id', $forecast->customer_id)
                    ->whereBetween('SalesInvoice.invoice_date', [
                        $startOfMonth->format('Y-m-d'),
                        $endOfMonth->format('Y-m-d')
                    ])
                    ->where('SalesInvoice.status', 'Paid')
                    ->sum('SalesInvoiceLine.quantity');
                
                $forecast->update([
                    'actual_quantity' => $actualQuantity,
                    'variance' => $actualQuantity - $forecast->forecast_quantity
                ]);
                
                $updatedCount++;
            }

            DB::commit();
            
            return response()->json(['message' => $updatedCount . ' forecasts updated with actual quantities'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update actual quantities', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get forecast accuracy metrics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getForecastAccuracy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:Customer,customer_id',
            'item_id' => 'nullable|exists:Item,item_id',
            'start_period' => 'required|date',
            'end_period' => 'required|date|after:start_period'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Get forecasts with actual quantities
            $query = SalesForecast::whereBetween('forecast_period', [$request->start_period, $request->end_period])
                ->whereNotNull('actual_quantity');
            
            // Apply filters if provided
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->has('item_id')) {
                $query->where('item_id', $request->item_id);
            }
            
            $forecasts = $query->get();
            
            // Calculate accuracy metrics
            $totalForecasts = count($forecasts);
            $totalForecastQuantity = $forecasts->sum('forecast_quantity');
            $totalActualQuantity = $forecasts->sum('actual_quantity');
            $totalAbsVariance = $forecasts->sum(function($forecast) {
                return abs($forecast->variance);
            });
            
            $mape = 0; // Mean Absolute Percentage Error
            $validForecastsForMAPE = 0;
            
            foreach ($forecasts as $forecast) {
                if ($forecast->actual_quantity > 0) {
                    $mape += abs($forecast->variance / $forecast->actual_quantity) * 100;
                    $validForecastsForMAPE++;
                }
            }
            
            if ($validForecastsForMAPE > 0) {
                $mape = $mape / $validForecastsForMAPE;
            }
            
            $bias = $totalForecastQuantity > 0 ? 
                (($totalActualQuantity - $totalForecastQuantity) / $totalForecastQuantity) * 100 : 0;
            
            $mad = $totalForecasts > 0 ? $totalAbsVariance / $totalForecasts : 0; // Mean Absolute Deviation
            
            return response()->json([
                'data' => [
                    'total_forecasts' => $totalForecasts,
                    'total_forecast_quantity' => $totalForecastQuantity,
                    'total_actual_quantity' => $totalActualQuantity,
                    'mean_absolute_percentage_error' => round($mape, 2),
                    'bias_percentage' => round($bias, 2),
                    'mean_absolute_deviation' => round($mad, 2),
                    'forecasts' => $forecasts
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to get forecast accuracy', 'error' => $e->getMessage()], 500);
        }
    }
}