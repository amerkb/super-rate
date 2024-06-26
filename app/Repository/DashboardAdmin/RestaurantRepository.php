<?php

namespace App\Repository\DashboardAdmin;

use App\Abstract\BaseRepositoryImplementation;
use App\ApiHelper\ApiResponseCodes;
use App\ApiHelper\ApiResponseHelper;
use App\ApiHelper\Result;
use App\Http\Resources\RestaurantResource;
use App\Interfaces\DashboardAdmin\RestaurantInterface;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RestaurantRepository extends BaseRepositoryImplementation implements RestaurantInterface
{
    public function getRestaurant()
    {
        $restaurants = $this->get();

        return RestaurantResource::collection($restaurants);
    }

    public function showRestaurant(Restaurant $restaurant)
    {
        return RestaurantResource::make($restaurant);
    }

    public function storeRestaurant(array $dataRestaurant)
    {
        $data = array_merge($dataRestaurant, ['password' => md5($dataRestaurant['password']), 'uuid' => Str::uuid()]);
        $restaurant = $this->create($data);
        $restaurant = RestaurantResource::make($restaurant);

        return ApiResponseHelper::sendResponse(new Result($restaurant, 'done'), ApiResponseCodes::CREATED);

    }

    public function storeRestaurantsByNumber(int $number)
    {
        try {
            DB::beginTransaction();
            $lastRestaurant = Restaurant::max('id');
            $restaurants = [];
            $password = [];

            // Perform the operations or code you want to measure

            for ($i = 0; $i < $number; $i++) {
                $password[$i] = rand(10000000, 99999999);
                $restaurants[$i] = [
                    'id' => $lastRestaurant + 1 + $i,
                    'password' => md5($password[$i]),
                    'name' => 'business '.$lastRestaurant + 1 + $i,
                    'uuid' => Str::uuid(),
                ];

            }
            $this->insert($restaurants);

            //         Create a new Excel file
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $sheet->setCellValue('A1', 'id');
            $sheet->setCellValue('B1', 'Restaurant');
            $sheet->setCellValue('C1', 'Password');
            $sheet->setCellValue('D1', 'uuid');
            $sheet->setCellValue('E1', 'link');

            $cellRange1 = 'A'. 1 .':E'. 1;
            $this->styling_sheet($sheet, $cellRange1);

            // Populate user data
            foreach ($restaurants as $index => $restaurant) {
                $row = $index + 2;
                $sheet->setCellValue('A'.$row, $restaurant['id']);
                $sheet->setCellValue('B'.$row, $restaurant['name']);
                $sheet->setCellValue('C'.$row, $password[$index]);
                $sheet->setCellValue('D'.$row, $restaurant['uuid']);
                $sheet->setCellValue('E'.$row, 'https://user.super-rate.tech/?business='.$restaurant['uuid']);

                // Set cell styling and spacing
                $cellRange = 'A'.$row.':E'.$row;
                $this->styling_sheet($sheet, $cellRange);
            }

            // Set the file name and save the Excel file
            $fileName = 'super_rate_users.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save($fileName);
            DB::commit();

            // Return the file as a response
            return response()->download($fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponseHelper::sendMessageResponse(
                $e->getMessage(), ApiResponseCodes::TIMEDOUT, false
            );
        }
    }

    public function updateRestaurant(array $dataRestaurant, Restaurant $restaurant)
    {
        $data = array_merge($dataRestaurant, ['password' => md5($dataRestaurant['password'])]);
        $restaurant = $this->updateById($restaurant->id, $data);

        return ApiResponseHelper::sendResponse(new Result($restaurant, 'done'));

    }

    public function deleteRestaurant(Restaurant $restaurant)
    {
        $this->deleteById($restaurant->id);

        return ApiResponseHelper::sendMessageResponse(
            'deleted successfully'
        );
    }

    public function model()
    {
        return Restaurant::class;
    }

    public function getFilterItems($filter)
    {
        // TODO: Implement getFilterItems() method.
    }

    public function styling_sheet(Worksheet $sheet, string $cellRange, int $columnWidth = 45, int $rowHeight = 35): void
    {
        $sheet->getStyle($cellRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($cellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($cellRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($cellRange)->getAlignment()->setWrapText(true);
        $sheet->getStyle($cellRange)->getAlignment()->setIndent(1);
        $sheet->getStyle($cellRange)->getAlignment()->setShrinkToFit(true);

        $sheet->getColumnDimension('A')->setWidth($columnWidth / 2);
        $sheet->getColumnDimension('B')->setWidth($columnWidth / 2);
        $sheet->getColumnDimension('C')->setWidth($columnWidth / 2);
        $sheet->getColumnDimension('D')->setWidth($columnWidth);
        $sheet->getColumnDimension('E')->setWidth($columnWidth);
        $highestRow = $sheet->getHighestRow();
        for ($row = 1; $row <= $highestRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight($rowHeight);
        }

        // Replace 'A' with the desired column letter or adjust accordingly for multiple columns
    }

    public function getRestaurantsByAttribute(Request $request)
    {

        $restaurants = Restaurant::query()
            ->when($request->category, function ($query) use ($request) {
                $query->whereHas('restaurant_details', function ($query) use ($request) {
                    $query->where('category', $request->category);
                });
            })
            ->when($request->phone, function ($query) use ($request) {
                $query->whereHas('restaurant_details', function ($query) use ($request) {
                    $query->where('phone', $request->phone);
                });
            })
            ->when($request->email, function ($query) use ($request) {
                $query->whereHas('restaurant_details', function ($query) use ($request) {
                    $query->where('email', $request->email);
                });
            })
            ->get();
        $restaurants = RestaurantResource::collection($restaurants);

        return ApiResponseHelper::sendResponse(new Result($restaurants, 'done'));

    }
}
