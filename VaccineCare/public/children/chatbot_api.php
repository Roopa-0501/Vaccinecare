<?php
header("Content-Type: application/json");

// ------------------------
// CONFIGURATION
// ------------------------

// Replace with your Replicate API key
$replicate_token = "r8_S5q6loa0mBBa5MCjIOIqiHo4vvNjgEo4I9kpc"; // leave empty if you don't have one

// Lightweight Replicate model version (Flan-T5 small)
$replicate_model_version = "7de6f0c83ef3d15a89b8e937a26b020e95ff1b88c08a99321b7df2f7f23d54d";

// ------------------------
// READ USER MESSAGE
// ------------------------
$data = json_decode(file_get_contents("php://input"), true);
$message = strtolower(trim($data["message"] ?? ""));

if (empty($message)) {
    echo json_encode(["success" => true, "reply" => "Please type a message."]);
    exit;
}

// ------------------------
// HARDCODED OFFLINE RESPONSES
// ------------------------
$responses = [
    "hello" => "Hi! Welcome to VaccineCare. I can provide information about child vaccinations, growth milestones, nutrition, and general health tips.",
    "hi" => "Hello! I can guide you about child vaccines, growth, and health.",
    
    // Vaccination details
    "vaccinations" => "Infant Vaccination Schedule:\n".
                      "0-3 months: BCG, Hepatitis B, OPV\n".
                      "6 weeks: DTP, Hib, Polio\n".
                      "10 weeks: DTP, Hib, Polio\n".
                      "14 weeks: DTP, Hib, Polio\n".
                      "9 months: Measles\n".
                      "15 months: MMR, Varicella\n".
                      "18 months: DTP booster, Hepatitis A\n".
                      "4-6 years: DTP, Polio booster, MMR second dose",
    
    "schedule" => "Vaccination schedule for children:\n".
                  "0-3 months: BCG, Hepatitis B, OPV\n".
                  "6 weeks: DTP, Hib, Polio\n".
                  "10 weeks: DTP, Hib, Polio\n".
                  "14 weeks: DTP, Hib, Polio\n".
                  "9 months: Measles\n".
                  "15 months: MMR, Varicella\n".
                  "18 months: DTP booster, Hepatitis A\n".
                  "4-6 years: DTP, Polio booster, MMR second dose",
    
    // Growth milestones
    "growth" => "Child Growth Milestones:\n".
                "0-3 months: Lifts head while on tummy, smiles responsively.\n".
                "4-6 months: Rolls over, starts sitting with support, babbling begins.\n".
                "7-9 months: Sits without support, crawls, understands simple words.\n".
                "10-12 months: Stands with support, may take first steps, imitates sounds.\n".
                "1-2 years: Walks independently, begins using simple words, shows curiosity.\n".
                "2-3 years: Runs, climbs stairs, forms short sentences, begins potty training.",
    
    // Nutrition
    "nutrition" => "Child Nutrition Tips:\n".
                   "0-6 months: Exclusive breastfeeding.\n".
                   "6-12 months: Introduce soft solids while continuing breastfeeding.\n".
                   "1-3 years: Balanced diet with fruits, vegetables, grains, proteins, and dairy.\n".
                   "Limit sugar and processed foods, ensure adequate hydration.",
    
    // Common health tips
    "health" => "Child Health Tips:\n".
                "- Regular vaccinations as per schedule.\n".
                "- Maintain hygiene and wash hands frequently.\n".
                "- Ensure safe sleep and play environment.\n".
                "- Regular growth and development check-ups.\n".
                "- Avoid exposure to sick contacts and crowded places if unwell.",
    
    // Default fallback
    "default" => "Sorry, I could not understand your request. You can ask me about:\n".
                 "- Vaccinations / schedule\n".
                 "- Growth milestones\n".
                 "- Nutrition\n".
                 "- General child health tips"
];

// ------------------------
// CHECK OFFLINE RESPONSES FIRST
// ------------------------
$reply = $responses["default"];
foreach ($responses as $key => $val) {
    if (strpos($message, $key) !== false) {
        $reply = $val;
        break;
    }
}

// ------------------------
// TRY ONLINE REPILCATE API IF KEY IS PROVIDED
// ------------------------
if (!empty($replicate_token)) {
    $url = "https://api.replicate.com/v1/predictions";
    $post_data = [
        "version" => $replicate_model_version,
        "input" => ["prompt" => "You are a helpful VaccineCare assistant. Answer clearly: $message"]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Token r8_S5q6loa0mBBa5MCjIOIqiHo4vvNjgEo4I9kpc",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 seconds timeout

    $result = curl_exec($ch);

    if (!curl_errno($ch)) {
        $response = json_decode($result, true);
        if (!empty($response["prediction"])) {
            $reply = $response["prediction"]; // use online response
        }
    }

    curl_close($ch);
}

// ------------------------
// RETURN RESPONSE
// ------------------------
echo json_encode(["success" => true, "reply" => $reply]);
?>
