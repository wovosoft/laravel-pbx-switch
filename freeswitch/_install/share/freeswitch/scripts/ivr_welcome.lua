-- Helper function to encode characters
local char_to_hex = function(c)
    return string.format("%%%02X", string.byte(c))
end

-- URL encoder for Bangla and special characters
local function urlencode(url)
    if url == nil then return end
    url = url:gsub("\n", "\r\n")
    url = url:gsub("([^%w ])", char_to_hex)
    url = url:gsub(" ", "+")
    return url
end

local function googleSpeak(text, time)
    text = urlencode(text);
    session:streamFile("http://127.0.0.1:8000/api/tts?text=" .. text);
    if time ~= nil then
        session:sleep(time);
    else
        session:sleep(500);
    end
end

local function googleSpeakIvrMenu()
    session:streamFile("http://127.0.0.1:8000/api/tts-ivr-menu");
    session:sleep(500);
end

local function playMenuAndGetDigits()
    local min_digits = 1
    local max_digits = 1
    local tries = 2
    local timeout = 5000
    local terminators = "#"
    local invalid_file = ""   -- can be another TTS/audio URL
    local digit_regex = "\\d" -- match single digit

    session:sleep(500);

    return session:playAndGetDigits(
        min_digits, max_digits, tries,
        timeout, terminators,
        "http://127.0.0.1:8000/api/tts-ivr-menu",
        invalid_file, digit_regex
    )
end



-- Manual URL encoding (in case LuaSocket isn't available)
function url_encode(str)
    if str == nil then return "" end
    str = string.gsub(str, "\n", "\r\n")
    str = string.gsub(str, "([^%w ])", function(c)
        return string.format("%%%02X", string.byte(c))
    end)
    return string.gsub(str, " ", "+")
end

local function accountBalanceQuery()
    googleSpeak("অনুগ্রহ পূর্বক আপনার ১৩ ডিজিটের হিসাব নম্বরটি প্রদান করুন।")

    -- Get 13-digit account number from user (wait 10 sec)
    local account_number = session:getDigits(13, "", 10000)

    if account_number ~= nil and account_number ~= "" then
        -- this value will be fetched from laravel api
        api_response = "২০,০০০";

        -- Speak result
        if api_response and api_response ~= "" then
            googleSpeak("আপনার বর্তমান ব্যাল্যান্স হলো:", 100)
            googleSpeak(api_response, 100);
            googleSpeak("টাকা মাত্র।");
            googleSpeak("বাংলাদেশ কৃষি ব্যাংকের সাথে থাকার জন্য ধন্যবাদ।");
        else
            googleSpeak("দুঃখিত, আপনার হিসাব নম্বরটি পাওয়া যায়নি।")
        end
    else
        googleSpeak("আপনি কোন হিসাব নম্বর প্রদান করেননি। অনুগ্রহ করে পরে আবার চেষ্টা করুন।")
    end
end

-- Start session
if session:ready() then
    session:answer();
    session:setAutoHangup(false);

    local digits = playMenuAndGetDigits()

    if digits == "1" then
        googleSpeak("আপনি বর্তমান ব্যাল্যান্স জানার অপশন বাছাই করেছেন।")
        accountBalanceQuery()
    elseif digits == "2" then
        googleSpeak("আপনি ২ চেপেছেন।")
    elseif digits == "3" then
        googleSpeak("আপনি ৩ চেপেছেন।")
    elseif digits == "4" then
        googleSpeak("আপনি ৪ চেপেছেন।")
    elseif digits == "5" then
        googleSpeak("আপনি ৫ চেপেছেন।")
    elseif digits == "0" then
        googleSpeak("আপনি ০ চেপেছেন। এখন আমরা আপনাকে একজন সেবা প্রতিনিধির সাথে সংযুক্ত করছি।")
        -- session:execute("transfer", "support_extension_or_sip_user_here")
    else
        googleSpeak("আপনি কোন সঠিক অপশন বাছাই করেননি। ধন্যবাদ।")
    end

    session:hangup()
end
