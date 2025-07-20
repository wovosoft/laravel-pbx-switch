local char_to_hex = function(c)
  return string.format("%%%02X", string.byte(c))
end

local function urlencode(url)
  if url == nil then
    return
  end
  url = url:gsub("\n", "\r\n")
  url = url:gsub("([^%w ])", char_to_hex)
  url = url:gsub(" ", "+")
  return url
end

-- Get your text (can be passed as an argument or set variable)
local raw_text = session:getVariable("tts_text")
if not raw_text then
  raw_text = "Default welcome text"
end

-- Encode
local encoded_text = urlencode(raw_text)

-- Use encoded_text to build your TTS URL
local tts_url = "http://127.0.0.1:8000/api/tts?text=" .. encoded_text

-- Play the URL
session:execute("playback", tts_url)
