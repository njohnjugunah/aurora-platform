<?php

namespace GlamByMariga\Communication;

use PDO;
use Exception;

/**
 * Content Generation Service
 * AI-assisted content creation and subject line optimization
 */
class ContentGenerationService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Generate subject line variants
     */
    public function generateSubjectLineVariants($originalSubject, $campaignContext = [])
    {
        try {
            $variants = [];

            // Variant 1: Add urgency/emotion
            $variants[] = [
                'subject' => $this->addUrgency($originalSubject),
                'type' => 'urgency',
                'pattern' => 'Adds time-limited urgency'
            ];

            // Variant 2: Personalization
            $variants[] = [
                'subject' => $this->addPersonalization($originalSubject),
                'type' => 'personalization',
                'pattern' => 'Adds personal touch'
            ];

            // Variant 3: Question format
            $variants[] = [
                'subject' => $this->convertToQuestion($originalSubject),
                'type' => 'question',
                'pattern' => 'Question-based engagement'
            ];

            // Variant 4: Benefit-focused
            $variants[] = [
                'subject' => $this->focusOnBenefit($originalSubject),
                'type' => 'benefit',
                'pattern' => 'Emphasizes customer benefits'
            ];

            // Variant 5: Curiosity/Intrigue
            $variants[] = [
                'subject' => $this->createCuriosity($originalSubject),
                'type' => 'curiosity',
                'pattern' => 'Sparks curiosity'
            ];

            // Score variants based on best practices
            foreach ($variants as &$variant) {
                $variant['predicted_open_rate'] = $this->scoreSubjectLine($variant['subject']);
            }

            // Sort by predicted performance
            usort($variants, function ($a, $b) {
                return $b['predicted_open_rate'] <=> $a['predicted_open_rate'];
            });

            return [
                'success' => true,
                'original' => $originalSubject,
                'variants' => $variants
            ];

        } catch (Exception $e) {
            error_log('Generate subject lines error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add urgency to subject line
     */
    private function addUrgency($subject)
    {
        $urgencyWords = ['Last chance', 'Limited time', 'Ending today', 'Don\'t miss out', 'Offer ends'];
        $word = $urgencyWords[array_rand($urgencyWords)];
        return "$word: $subject";
    }

    /**
     * Add personalization to subject line
     */
    private function addPersonalization($subject)
    {
        $personalization = ['You deserve', 'Just for you', 'Exclusive for you', 'Your special'];
        $word = $personalization[array_rand($personalization)];
        return "$word - $subject";
    }

    /**
     * Convert subject to question format
     */
    private function convertToQuestion($subject)
    {
        // Simple approach: add question words
        $questions = ['Ready to', 'Want to', 'Interested in', 'Looking for'];
        $question = $questions[array_rand($questions)];
        return "$question $subject?";
    }

    /**
     * Focus on benefits
     */
    private function focusOnBenefit($subject)
    {
        $benefits = ['Save time', 'Save money', 'Get more', 'Achieve', 'Discover'];
        $benefit = $benefits[array_rand($benefits)];
        return "$benefit with $subject";
    }

    /**
     * Create curiosity gap
     */
    private function createCuriosity($subject)
    {
        $curiosity = ['Surprising ', 'Shocking ', 'Unbelievable ', 'What if ', '⚡ '];
        $word = $curiosity[array_rand($curiosity)];
        return $word . $subject;
    }

    /**
     * Score subject line effectiveness (0-100)
     */
    private function scoreSubjectLine($subject)
    {
        $score = 50; // Base score

        // Length: 30-50 characters optimal
        $length = strlen($subject);
        if ($length >= 30 && $length <= 50) {
            $score += 20;
        } elseif ($length >= 20 && $length <= 60) {
            $score += 10;
        }

        // Contains numbers (proven engagement boost)
        if (preg_match('/\d+/', $subject)) {
            $score += 15;
        }

        // Contains power words
        $powerWords = ['exclusive', 'new', 'limited', 'free', 'save', 'discover', 'special', 'proven', 'revolutionary'];
        foreach ($powerWords as $word) {
            if (stripos($subject, $word) !== false) {
                $score += 8;
                break;
            }
        }

        // Starts with capital (professionalism)
        if (ctype_upper($subject[0])) {
            $score += 5;
        }

        // No all caps (spam trigger)
        if (preg_match('/[A-Z]{5,}/', $subject)) {
            $score -= 15;
        }

        // Sentiment analysis (basic)
        if (preg_match('/!+$/', $subject)) {
            if (preg_match('/!{3,}/', $subject)) {
                $score -= 20; // Multiple exclamation marks = spam
            } else {
                $score += 5;
            }
        }

        return min(95, max(20, round($score)));
    }

    /**
     * Generate subject line recommendation
     */
    public function recommendSubjectLine($campaignId, $productName = null, $offerType = null)
    {
        try {
            // Get historical performance for similar offers
            $stmt = $this->db->prepare(
                "SELECT subject_line, open_rate
                 FROM subject_line_performance
                 WHERE open_rate IS NOT NULL
                 ORDER BY open_rate DESC
                 LIMIT 20"
            );
            $stmt->execute();
            $topPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Extract patterns from top performers
            $baseSubject = $this->buildSubjectFromPatterns($topPerformers, $productName, $offerType);

            // Generate variants
            $variants = $this->generateSubjectLineVariants($baseSubject);

            return [
                'success' => true,
                'recommended' => $variants['variants'][0] ?? $baseSubject,
                'alternatives' => array_slice($variants['variants'], 1, 2) ?? []
            ];

        } catch (Exception $e) {
            error_log('Recommend subject line error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build subject from top performing patterns
     */
    private function buildSubjectFromPatterns($topPerformers, $productName = null, $offerType = null)
    {
        if (empty($topPerformers)) {
            return $productName ? "Introducing $productName" : "Special Offer Just for You";
        }

        // Analyze patterns from top performers
        $patterns = [];
        foreach ($topPerformers as $performer) {
            if (preg_match('/(\d+%|\d+ off)/', $performer['subject_line'], $matches)) {
                $patterns['discount']++;
            }
            if (preg_match('/(exclusive|limited|special)/', $performer['subject_line'])) {
                $patterns['exclusivity']++;
            }
            if (preg_match('/(new|fresh|latest)/', $performer['subject_line'])) {
                $patterns['novelty']++;
            }
        }

        // Build based on dominant pattern
        arsort($patterns);
        $dominantPattern = key($patterns);

        if ($dominantPattern === 'exclusivity') {
            return "Exclusive: " . ($productName ?? "Special Offer");
        } elseif ($dominantPattern === 'novelty') {
            return "New: " . ($productName ?? "Discover Something Special");
        } else {
            return ($productName ?? "Beauty Essentials") . " - Limited Time";
        }
    }

    /**
     * Store generated content variant
     */
    public function storeGeneratedVariant($campaignId, $contentType, $variants, $selectedVariant = null)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO ai_generated_content
                 (campaign_id, content_type, generated_variants, selected_variant)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $campaignId,
                $contentType,
                json_encode($variants),
                $selectedVariant ?? $variants[0]['subject'] ?? null
            ]);

            return [
                'success' => true,
                'content_id' => $this->db->lastInsertId()
            ];

        } catch (Exception $e) {
            error_log('Store generated content error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Track subject line performance
     */
    public function trackSubjectLinePerformance($campaignId, $subjectLine, $variantType = 'original')
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO subject_line_performance
                 (campaign_id, subject_line, variant_type)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$campaignId, $subjectLine, $variantType]);

            return ['success' => true];

        } catch (Exception $e) {
            error_log('Track subject line error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get subject line analytics
     */
    public function getSubjectLineAnalytics($limit = 20)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT subject_line, sent_count, open_rate, click_rate, conversion_rate
                 FROM subject_line_performance
                 WHERE sent_count > 0
                 ORDER BY open_rate DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get subject line analytics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate CTA recommendations
     */
    public function generateCTARecommendations($campaignType)
    {
        $recommendations = [
            'promotional' => [
                'Shop Now',
                'Get the Deal',
                'Claim Your Offer',
                'Save Today'
            ],
            'educational' => [
                'Learn More',
                'Read the Guide',
                'Discover Tips',
                'Explore Now'
            ],
            'win_back' => [
                'Come Back',
                'We Miss You',
                'Reconnect',
                'Get Your Discount'
            ],
            'reorder' => [
                'Reorder Now',
                'Shop Again',
                'Buy More',
                'Continue Shopping'
            ]
        ];

        return [
            'success' => true,
            'recommendations' => $recommendations[$campaignType] ?? $recommendations['promotional'],
            'description' => 'Recommended CTAs for ' . $campaignType . ' campaigns'
        ];
    }
}
