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

-- Get the TTS text variable if provided
local raw_text = session:getVariable("tts_text") or "Default welcome text"
local base_url = "http://127.0.0.1:8000/api/tts?text="
local encoded_text = urlencode(raw_text)
local tts_url = base_url .. encoded_text

-- Start IVR session
session:answer()
session:streamFile(tts_url)  -- Initial message
session:sleep(1000)

-- Main IVR Menu
session:streamFile(base_url .. urlencode("বাংলাদেশ কৃষি ব্যাংকে আপনাকে স্বাগতম। সেবা পেতে ১ চাপুন, অপারেটরের সাথে কথা বলতে ২ চাপুন।"))

-- Collect DTMF input: 1 digit, max 3 tries, 5 sec timeout
local digits = session:playAndGetDigits(1, 1, 3, 5000, "#", "", "", "\\d")

if digits == "1" then
    session:streamFile(base_url .. urlencode("আপনার ব্যালেন্স জানতে আমাদের ওয়েবসাইটে ভিজিট করুন।"))
elseif digits == "2" then
    session:streamFile(base_url .. urlencode("আপনার কল একজন অপারেটরে স্থানান্তর করা হচ্ছে।"))
--     session:execute("transfer", "1000 XML default")
    session:execute("transfer", "sofia/gateway/bdwebs/01764954227")
    session:sleep(1000)
else
    session:streamFile(base_url .. urlencode("ভুল ইনপুট। আবার চেষ্টা করুন।"))
end

-- Add sleep if you want to ensure TTS playback finishes before hangup
session:sleep(1000)
session:hangup()
