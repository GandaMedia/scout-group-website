<?php

namespace App\Services\MealPlanner;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class MealPlannerExcelExporter
{
    /**
     * @param  array<string, mixed>  $export
     */
    public function writeToStream(array $export): void
    {
        $writer = new Writer;
        $writer->openToFile('php://output');

        $this->writeSummarySheet($writer, $export);
        $this->writeMealLinesSheet($writer, $export);

        $writer->close();
    }

    /**
     * @param  array<string, mixed>  $export
     */
    private function writeSummarySheet(Writer $writer, array $export): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Summary');
        $sheet->setColumnWidth(26, 1);
        $sheet->setColumnWidth(18, 2);

        $project = $export['project'];
        $totals = $project['totals'];

        $writer->addRow($this->row(['Meal planner export'], $this->headerStyle()));
        $writer->addRow($this->row(['Project', $project['name']]));
        $writer->addRow($this->row(['People', $project['people_count']]));
        $writer->addRow($this->row(['Event date', $project['event_date']]));
        $writer->addRow($this->row(['Total cost', $this->pounds($totals['total_cost_minor'])]));
        $writer->addRow($this->row(['Cost per head', $this->pounds($totals['cost_per_head_minor'])]));
        $writer->addRow($this->row(['Calories per serving', $totals['total_calories_per_serving']]));
        $writer->addRow($this->row(['Meals', $totals['meal_count']]));
        $writer->addRow($this->row(['Generated at', $export['generated_at']]));
    }

    /**
     * @param  array<string, mixed>  $export
     */
    private function writeMealLinesSheet(Writer $writer, array $export): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Meal lines');
        $sheet->setColumnWidthForRange(16, 1, 3);
        $sheet->setColumnWidthForRange(24, 4, 6);
        $sheet->setColumnWidthForRange(15, 7, 13);

        $writer->addRow($this->row([
            'Day',
            'Meal type',
            'Meal',
            'Food',
            'Brand',
            'Store',
            'Qty per serving',
            'Packs',
            'Pack price',
            'Cost per serving',
            'Calories per serving',
            'Total cost',
            'Price date',
        ], $this->headerStyle()));

        foreach ($export['meals'] as $meal) {
            foreach ($meal['lines'] as $line) {
                $writer->addRow($this->row([
                    $meal['day_number'] === null ? null : 'Day '.$meal['day_number'],
                    $meal['meal_type'],
                    $meal['name'],
                    $line['food']['name'],
                    $line['food']['brand'],
                    $line['food']['store'],
                    $line['amount_per_serving'],
                    $line['packs_required'],
                    $this->pounds($line['price_per_pack_minor']),
                    $this->pounds($line['cost_per_serving_minor']),
                    $line['calories_per_serving'],
                    $this->pounds($line['total_cost_minor']),
                    $line['priced_at'],
                ]));
            }
        }
    }

    /**
     * @param  list<mixed>  $values
     */
    private function row(array $values, ?Style $style = null): Row
    {
        return Row::fromValues($values, $style);
    }

    private function headerStyle(): Style
    {
        return (new Style)->setFontBold();
    }

    private function pounds(int $minor): float
    {
        return $minor / 100;
    }
}
