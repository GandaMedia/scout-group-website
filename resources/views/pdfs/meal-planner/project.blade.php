<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $export['project']['name'] }} meal planner</title>
    <style>
        body {
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        h1, h2 {
            margin: 0;
        }

        h1 {
            font-size: 24px;
        }

        h2 {
            font-size: 16px;
            margin-top: 24px;
        }

        .muted {
            color: #64748b;
        }

        .summary {
            border-collapse: collapse;
            margin-top: 16px;
            width: 100%;
        }

        .summary td {
            border: 1px solid #cbd5e1;
            padding: 8px;
        }

        table.lines {
            border-collapse: collapse;
            margin-top: 8px;
            width: 100%;
        }

        .lines th {
            background: #1e3a8a;
            color: #ffffff;
            font-size: 9px;
            padding: 6px;
            text-align: left;
            text-transform: uppercase;
        }

        .lines td {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }
    </style>
</head>
<body>
@php
    $money = fn (int $minor): string => '£'.number_format($minor / 100, 2);
    $project = $export['project'];
    $totals = $project['totals'];
@endphp

<h1>{{ $project['name'] }} meal planner</h1>
<p class="muted">
    {{ $project['people_count'] }} people -
    {{ $project['event_date'] }}
</p>

<table class="summary">
    <tr>
        <td><strong>Total</strong><br>{{ $money($totals['total_cost_minor']) }}</td>
        <td><strong>Per head</strong><br>{{ $money($totals['cost_per_head_minor']) }}</td>
        <td><strong>Calories</strong><br>{{ $totals['total_calories_per_serving'] }}</td>
        <td><strong>Meals</strong><br>{{ $totals['meal_count'] }}</td>
    </tr>
</table>

@forelse ($export['meals'] as $meal)
    <h2>
        @if ($meal['day_number'])
            Day {{ $meal['day_number'] }} -
        @endif
        {{ $meal['name'] }}
    </h2>
    <p class="muted">
        {{ $meal['meal_type'] }} -
        {{ $meal['totals']['calories_per_serving'] }} calories -
        {{ $money($meal['totals']['cost_per_serving_minor']) }} per serving
    </p>

    <table class="lines">
        <thead>
        <tr>
            <th>Item</th>
            <th class="right">Qty</th>
            <th class="right">Packs</th>
            <th class="right">Pack price</th>
            <th class="right">Serving</th>
            <th class="right">Calories</th>
            <th class="right">Total</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($meal['lines'] as $line)
            <tr>
                <td>
                    <strong>{{ $line['food']['name'] }}</strong><br>
                    <span class="muted">{{ $line['food']['store'] }} - {{ $line['food']['brand'] }}</span>
                </td>
                <td class="right">{{ number_format($line['amount_per_serving'], 2) }}</td>
                <td class="right">{{ $line['packs_required'] }}</td>
                <td class="right">{{ $money($line['price_per_pack_minor']) }}</td>
                <td class="right">{{ $money($line['cost_per_serving_minor']) }}</td>
                <td class="right">{{ $line['calories_per_serving'] }}</td>
                <td class="right">{{ $money($line['total_cost_minor']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="muted">No food items added.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@empty
    <p class="muted">No meals added.</p>
@endforelse
</body>
</html>
