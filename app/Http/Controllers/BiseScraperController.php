<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Subject;

class BiseScraperController extends Controller
{
    /**
     * Fetch result from BISE Bahawalpur website using direct link
     */
    public function fetchResult(Request $request)
    {
        $request->validate([
            'roll_no' => 'required|string',
            'class_level' => 'nullable|in:11,12', // Added class_level support
        ]);

        $rollNo = $request->roll_no;
        $classLevel = $request->input('class_level', '12'); // Default to 12
        $encodedRoll = base64_encode($rollNo);
        
        $part = ($classLevel == '11') ? 'PI' : 'PII';
        $url = "https://results.bisebwp.pk/HSSC_{$part}.aspx?data=" . $encodedRoll;
        $referer = "https://results.bisebwp.pk/indexHSSC_{$part}.aspx";

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer' => $referer,
                ])
                ->timeout(30)
                ->get($url);
            
            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to connect to BISE website.'], 500);
            }

            $html = $response->body();
            $crawler = new Crawler($html);
            
            // Check for Absent status if table is missing
            $isOverallAbsent = stripos($html, 'Absent-R/A') !== false || stripos($html, 'The candidate has been absent') !== false;

            // The results are typically in a table inside #print_content
            $rows = $crawler->filter('#print_content table[border="1"] tr');
            
            if ($rows->count() <= 2) {
                if ($isOverallAbsent) {
                    return response()->json([
                        'roll_no' => $rollNo,
                        'url' => $url,
                        'status' => 'Absent',
                        'data' => []
                    ]);
                }
                return response()->json(['error' => 'Result not found or table structure changed.'], 404);
            }

            $marks = [];
            
            // Detect if this is the 11th grade layout (single column for Part I)
            $isPart1Layout = ($classLevel == '11');

            $rows->each(function (Crawler $row) use (&$marks, $isPart1Layout) {
                $cols = $row->filter('td');
                
                if ($isPart1Layout) {
                    // 11th Grade (Part I) Layout: Subject, Max, Obtained, Percentile, Grade, Status
                    if ($cols->count() >= 3) {
                        $sub = trim($cols->eq(0)->text());
                        $obtained = trim($cols->eq(2)->text());
                        
                        // Ignore header, sub-header (Part I), and Grand Total rows
                        if (in_array($sub, ['Subject', 'Part I', 'Grand Total']) || strpos($sub, 'BOARD OF') !== false) {
                            return;
                        }

                        $clean = function($val) {
                            $val = trim($val);
                            if ($val === '--' || $val === '' || $val === '&nbsp;' || $val === ' ') return null;
                            if (stripos($val, 'Absent') !== false) return 'A';
                            return is_numeric($val) ? (int)$val : null;
                        };

                        if ($sub && $obtained !== null) {
                            // Extract root subject name (removes I at end)
                            $root = preg_replace('/(?<=..)(I|II|III|IV|V|VI|VII|VIII)[A-Z]?$/', '', $sub);
                            
                            $marks[] = [
                                'board_subject' => $root ?: $sub,
                                'p1_theory' => $clean($obtained),
                                'p2_theory' => null,
                                'practical' => null,
                            ];
                        }
                    }
                } else {
                    // 12th Grade (Part II) Layout: Subject, P1, Subject2, P2, Practical (or similar)
                    if ($cols->count() >= 5) {
                        $sub1 = trim($cols->eq(0)->text());
                        $m1 = trim($cols->eq(1)->text());
                        $sub2 = trim($cols->eq(2)->text());
                        $m2 = trim($cols->eq(3)->text());
                        $prac = trim($cols->eq(4)->text());

                        // Ignore header rows
                        if ($sub1 === 'Subject' || strpos($sub1, 'BOARD OF') !== false || $sub1 === 'Grand Total' || $sub1 === 'Part I') {
                            return;
                        }

                        $clean = function($val) {
                            $val = trim($val);
                            if ($val === '--' || $val === '' || $val === '&nbsp;' || $val === ' ') return null;
                            if (stripos($val, 'Absent') !== false) return 'A';
                            return is_numeric($val) ? (int)$val : null;
                        };

                        $root1 = preg_replace('/(?<=..)(I|II|III|IV|V|VI|VII|VIII)[A-Z]?$/', '', $sub1);
                        $root2 = preg_replace('/(?<=..)(I|II|III|IV|V|VI|VII|VIII)[A-Z]?$/', '', $sub2);

                        if ($root1 === $root2 || $sub2 === '--' || $sub2 === '' || $sub2 === ' ') {
                            $marks[] = [
                                'board_subject' => $root1 ?: $sub1,
                                'p1_theory' => $clean($m1),
                                'p2_theory' => $clean($m2),
                                'practical' => $clean($prac),
                            ];
                        } else {
                            if ($sub1 && $sub1 !== '--' && $sub1 !== ' ') {
                                $marks[] = [
                                    'board_subject' => $sub1,
                                    'p1_theory' => $clean($m1),
                                    'p2_theory' => null,
                                    'practical' => null,
                                ];
                            }
                            if ($sub2 && $sub2 !== '--' && $sub2 !== ' ') {
                                $marks[] = [
                                    'board_subject' => $sub2,
                                    'p1_theory' => null,
                                    'p2_theory' => $clean($m2),
                                    'practical' => $clean($prac),
                                ];
                            }
                        }
                    }
                }
            });

            return response()->json([
                'roll_no' => $rollNo,
                'url' => $url,
                'data' => $marks
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
