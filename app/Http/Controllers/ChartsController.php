<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use QuickChart;
class ChartsController extends Controller
{
    public function showChart1(Request $request)
    {
        $datos = $request->datos;
        //dd($datos);
        $cuenta_l1 = $datos[0]['cuenta'];
        $cuenta_l2 = $datos[1]['cuenta'];
        $cuenta_l3 = $datos[2]['cuenta'];
        $cuenta_l4 = $datos[3]['cuenta'];
        $cuenta_i1 = floatval($datos[0]['importe']);
        $cuenta_i2 = floatval($datos[1]['importe']);
        $cuenta_i3 = floatval($datos[2]['importe']);
        $cuenta_i4 = floatval($datos[3]['importe']);
        //dd($cuenta_l1,$cuenta_l2,$cuenta_l3,$cuenta_l4,$cuenta_i1,$cuenta_i2,$cuenta_i4,$cuenta_i4);
        $config = <<<EOD
        {
          type: 'doughnut',
          data: {
            datasets: [
              {
                data: [$cuenta_i1, $cuenta_i2, $cuenta_i3, $cuenta_i4],
                backgroundColor: [
                  'rgb(255, 99, 132)',
                  'rgb(255, 159, 64)',
                  'rgb(255, 205, 86)',
                  'rgb(75, 192, 192)',
                ],
              },
            ],
            labels: ['$cuenta_l1', '$cuenta_l2', '$cuenta_l3', '$cuenta_l4'],
            },
              options: {
                  plugins: {
                    tickFormat: {
                      style: 'currency',
                      currency: 'USD',
                      minimumFractionDigits: 2,
                    }
              }
            }
        }
        EOD;
        //dd($config);
        $qc = new QuickChart([
            'width' => 450,
            'height' => 450,
        ]);
        $qc->setConfig($config);
        //dd($qc->getUrl());
        return $qc->getUrl();
    }
    public function showChart2(Request $request)
    {
        $datos = $request->datos;
        //dd($datos);
        $cuenta_l1 = $datos[0]['cuenta'];
        $cuenta_l2 = $datos[1]['cuenta'];
        $cuenta_l3 = $datos[2]['cuenta'];
        $cuenta_l4 = $datos[3]['cuenta'];
        $cuenta_i1 = floatval($datos[0]['importe']);
        $cuenta_i2 = floatval($datos[1]['importe']);
        $cuenta_i3 = floatval($datos[2]['importe']);
        $cuenta_i4 = floatval($datos[3]['importe']);
        //dd($cuenta_l1,$cuenta_l2,$cuenta_l3,$cuenta_l4,$cuenta_i1,$cuenta_i2,$cuenta_i4,$cuenta_i4);
        $config = <<<EOD
        {
          type: 'doughnut',
          data: {
            datasets: [
              {
                data: [$cuenta_i1, $cuenta_i2, $cuenta_i3, $cuenta_i4],
                backgroundColor: [
                  'rgb(255, 99, 132)',
                  'rgb(255, 159, 64)',
                  'rgb(255, 205, 86)',
                  'rgb(75, 192, 192)',
                ],
              },
            ],
            labels: ['$cuenta_l1', '$cuenta_l2', '$cuenta_l3', '$cuenta_l4'],
            },
              options: {
                  plugins: {
                    tickFormat: {
                      style: 'currency',
                      currency: 'USD',
                      minimumFractionDigits: 2,
                    }
              }
            }
        }
        EOD;
        //dd($config);
        $qc = new QuickChart([
            'width' => 450,
            'height' => 450,
        ]);
        $qc->setConfig($config);
        //dd($qc->getUrl());
        return $qc->getUrl();
    }

    public function showChart3(Request $request){
        $datos = $request->datos;
        //dd($datos);
        $cuenta_l1 = $datos[0]['cuenta'];
        $cuenta_l2 = $datos[1]['cuenta'];
        $cuenta_l3 = $datos[2]['cuenta'];
        $cuenta_l4 = $datos[3]['cuenta'];
        $cuenta_i1 = floatval($datos[0]['importe']);
        $cuenta_i2 = floatval($datos[1]['importe']);
        $cuenta_i3 = floatval($datos[2]['importe']);
        $cuenta_i4 = floatval($datos[3]['importe']);
        //dd($cuenta_l1,$cuenta_l2,$cuenta_l3,$cuenta_l4,$cuenta_i1,$cuenta_i2,$cuenta_i4,$cuenta_i4);
        $config = <<<EOD
        {
          type: 'doughnut',
          data: {
            datasets: [
              {
                data: [$cuenta_i1, $cuenta_i2, $cuenta_i3, $cuenta_i4],
                backgroundColor: [
                  'rgb(255, 99, 132)',
                  'rgb(255, 159, 64)',
                  'rgb(255, 205, 86)',
                  'rgb(75, 192, 192)',
                ],
              },
            ],
            labels: ['$cuenta_l1', '$cuenta_l2', '$cuenta_l3', '$cuenta_l4'],
            },
              options: {
                  plugins: {
                    tickFormat: {
                      style: 'currency',
                      currency: 'USD',
                      minimumFractionDigits: 2,
                    }
              }
            }
        }
        EOD;
        //dd($config);
        $qc = new QuickChart([
            'width' => 450,
            'height' => 450,
        ]);
        $qc->setConfig($config);
        //dd($qc->getUrl());
        return $qc->getUrl();
    }
    public function showChart4(Request $request){
        $datos = $request->datos;
        //dd($datos);
        $cuenta_l1 = $datos[0]['cuenta'];
        $cuenta_l2 = $datos[1]['cuenta'];
        $cuenta_l3 = $datos[2]['cuenta'];
        $cuenta_l4 = $datos[3]['cuenta'];
        $cuenta_i1 = floatval($datos[0]['importe']);
        $cuenta_i2 = floatval($datos[1]['importe']);
        $cuenta_i3 = floatval($datos[2]['importe']);
        $cuenta_i4 = floatval($datos[3]['importe']);
        //dd($cuenta_l1,$cuenta_l2,$cuenta_l3,$cuenta_l4,$cuenta_i1,$cuenta_i2,$cuenta_i4,$cuenta_i4);
        $config = <<<EOD
        {
          type: 'doughnut',
          data: {
            datasets: [
              {
                data: [$cuenta_i1, $cuenta_i2, $cuenta_i3, $cuenta_i4],
                backgroundColor: [
                  'rgb(255, 99, 132)',
                  'rgb(255, 159, 64)',
                  'rgb(255, 205, 86)',
                  'rgb(75, 192, 192)',
                ],
              },
            ],
            labels: ['$cuenta_l1', '$cuenta_l2', '$cuenta_l3', '$cuenta_l4'],
            },
              options: {
                  plugins: {
                    tickFormat: {
                      style: 'currency',
                      currency: 'USD',
                      minimumFractionDigits: 2,
                    }
              }
            }
        }
        EOD;
        //dd($config);
        $qc = new QuickChart([
            'width' => 450,
            'height' => 450,
        ]);
        $qc->setConfig($config);
        //dd($qc->getUrl());
        return $qc->getUrl();
    }
    public function showChart5(Request $request){
        $datos = $request->datos;
        //dd($datos);
        $cuenta_l1 = $datos[0]['cuenta'];
        $cuenta_l2 = $datos[1]['cuenta'];
        $cuenta_l3 = $datos[2]['cuenta'];
        $cuenta_l4 = $datos[3]['cuenta'];
        $cuenta_i1 = floatval($datos[0]['importe']);
        $cuenta_i2 = floatval($datos[1]['importe']);
        $cuenta_i3 = floatval($datos[2]['importe']);
        $cuenta_i4 = floatval($datos[3]['importe']);
        //dd($cuenta_l1,$cuenta_l2,$cuenta_l3,$cuenta_l4,$cuenta_i1,$cuenta_i2,$cuenta_i4,$cuenta_i4);
        $config = <<<EOD
        {
          type: 'doughnut',
          data: {
            datasets: [
              {
                data: [$cuenta_i1, $cuenta_i2, $cuenta_i3, $cuenta_i4],
                backgroundColor: [
                  'rgb(255, 99, 132)',
                  'rgb(255, 159, 64)',
                  'rgb(255, 205, 86)',
                  'rgb(75, 192, 192)',
                ],
              },
            ],
            labels: ['$cuenta_l1', '$cuenta_l2', '$cuenta_l3', '$cuenta_l4'],
            },
              options: {
                  plugins: {
                    tickFormat: {
                      style: 'currency',
                      currency: 'USD',
                      minimumFractionDigits: 2,
                    }
              }
            }
        }
        EOD;
        //dd($config);
        $qc = new QuickChart([
            'width' => 450,
            'height' => 450,
        ]);
        $qc->setConfig($config);
        //dd($qc->getUrl());
        return $qc->getUrl();
    }
    public function showChart6(Request $request){
        $datos = $request->datos;
        //dd($datos);
        $cuenta_l1 = $datos[0]['cuenta'];
        $cuenta_l2 = $datos[1]['cuenta'];
        $cuenta_l3 = $datos[2]['cuenta'];
        $cuenta_l4 = $datos[3]['cuenta'];
        $cuenta_i1 = floatval($datos[0]['importe']);
        $cuenta_i2 = floatval($datos[1]['importe']);
        $cuenta_i3 = floatval($datos[2]['importe']);
        $cuenta_i4 = floatval($datos[3]['importe']);
        //dd($cuenta_l1,$cuenta_l2,$cuenta_l3,$cuenta_l4,$cuenta_i1,$cuenta_i2,$cuenta_i4,$cuenta_i4);
        $config = <<<EOD
        {
          type: 'doughnut',
          data: {
            datasets: [
              {
                data: [$cuenta_i1, $cuenta_i2, $cuenta_i3, $cuenta_i4],
                backgroundColor: [
                  'rgb(255, 99, 132)',
                  'rgb(255, 159, 64)',
                  'rgb(255, 205, 86)',
                  'rgb(75, 192, 192)',
                ],
              },
            ],
            labels: ['$cuenta_l1', '$cuenta_l2', '$cuenta_l3', '$cuenta_l4'],
            },
              options: {
                  plugins: {
                    tickFormat: {
                      style: 'currency',
                      currency: 'USD',
                      minimumFractionDigits: 2,
                    }
              }
            }
        }
        EOD;
        //dd($config);
        $qc = new QuickChart([
            'width' => 450,
            'height' => 450,
        ]);
        $qc->setConfig($config);
        //dd($qc->getUrl());
        return $qc->getUrl();
    }
    public function showChart7(Request $request){
        $datos = $request->datos;
        //dd($datos);
        $cuenta_l1 = $datos[0]['cuenta'];
        $cuenta_l2 = $datos[1]['cuenta'];
        $cuenta_l3 = $datos[2]['cuenta'];
        $cuenta_i1 = floatval($datos[0]['importe']);
        $cuenta_i2 = floatval($datos[1]['importe']);
        $cuenta_i3 = floatval($datos[2]['importe']);
        //dd($cuenta_l1,$cuenta_l2,$cuenta_l3,$cuenta_l4,$cuenta_i1,$cuenta_i2,$cuenta_i4,$cuenta_i4);
        $config = <<<EOD
        {
          type: 'doughnut',
          data: {
            datasets: [
              {
                data: [$cuenta_i1, $cuenta_i2, $cuenta_i3],
                backgroundColor: [
                  'rgb(255, 99, 132)',
                  'rgb(255, 159, 64)',
                  'rgb(255, 205, 86)',
                  'rgb(75, 192, 192)',
                ],
              },
            ],
            labels: ['$cuenta_l1', '$cuenta_l2', '$cuenta_l3'],
            },
              options: {
                  plugins: {
                    tickFormat: {
                      style: 'currency',
                      currency: 'USD',
                      minimumFractionDigits: 2,
                    }
              }
            }
        }
        EOD;
        //dd($config);
        $qc = new QuickChart([
            'width' => 450,
            'height' => 450,
        ]);
        $qc->setConfig($config);
        //dd($qc->getUrl());
        return $qc->getUrl();
    }
    public function showChart8(Request $request){
        $datos = $request->datos;
        //dd($datos);
        $cuenta_l1 = $datos[0]['cuenta'];
        $cuenta_l2 = $datos[1]['cuenta'];
        $cuenta_l3 = $datos[2]['cuenta'];
        $cuenta_i1 = floatval($datos[0]['importe']);
        $cuenta_i2 = floatval($datos[1]['importe']);
        $cuenta_i3 = floatval($datos[2]['importe']);
        //dd($cuenta_l1,$cuenta_l2,$cuenta_l3,$cuenta_l4,$cuenta_i1,$cuenta_i2,$cuenta_i4,$cuenta_i4);
        $config = <<<EOD
        {
          type: 'doughnut',
          data: {
            datasets: [
              {
                data: [$cuenta_i1, $cuenta_i2, $cuenta_i3],
                backgroundColor: [
                  'rgb(255, 99, 132)',
                  'rgb(255, 159, 64)',
                  'rgb(255, 205, 86)',
                  'rgb(75, 192, 192)',
                ],
              },
            ],
            labels: ['$cuenta_l1', '$cuenta_l2', '$cuenta_l3'],
            },
              options: {
                  plugins: {
                    tickFormat: {
                      style: 'currency',
                      currency: 'USD',
                      minimumFractionDigits: 2,
                    }
              }
            }
        }
        EOD;
        //dd($config);
        $qc = new QuickChart([
            'width' => 450,
            'height' => 450,
        ]);
        $qc->setConfig($config);
        //dd($qc->getUrl());
        return $qc->getUrl();
    }
}
