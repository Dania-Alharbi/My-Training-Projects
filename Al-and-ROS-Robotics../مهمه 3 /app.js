const micBtn = document.getElementById("micBtn");
const micIcon = document.getElementById("micIcon");
const audioFileInput = document.getElementById("audioFileInput");

const sendBtn = document.getElementById("sendBtn");
const textInput = document.getElementById("textInput");

const chatLog = document.getElementById("chatLog");
const statusText = document.getElementById("statusText");

const BACKEND_URL = "ro.php";

// =====================================================
// إضافة رسالة
// =====================================================

function addMessage(role, text, options = {}) {

const el = document.createElement("div");

el.className =
    "message " +
    role +
    (options.thinking ? " thinking" : "");

const p = document.createElement("p");

p.textContent = text;

el.appendChild(p);

chatLog.appendChild(el);

chatLog.scrollTop = chatLog.scrollHeight;

return el;

}

// =====================================================
// قراءة الرد بصوت
// =====================================================

function speak(text) {

if (!("speechSynthesis" in window)) {
    return;
}

window.speechSynthesis.cancel();

const utterance =
    new SpeechSynthesisUtterance(text);

utterance.lang = "ar-SA";
utterance.rate = 1;

window.speechSynthesis.speak(utterance);

}

// =====================================================
// إرسال النص إلى ro.php
// =====================================================

async function sendText(text) {

text = String(text || "").trim();

if (!text) {
    return;
}

addMessage("user", text);

const thinking =
    addMessage(
        "bot",
        "جاري التفكير...",
        { thinking: true }
    );

statusText.textContent =
    "جاري الاتصال بالخادم...";

try {

    const response =
        await fetch(
            BACKEND_URL,
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json",

                    "Accept":
                        "application/json"
                },

                body: JSON.stringify({
                    prompt: text
                })
            }
        );


    const raw =
        await response.text();


    console.log(
        "RO.PHP RESPONSE:",
        raw
    );


    thinking.remove();


    let data;


    try {

        data =
            JSON.parse(raw);

    } catch (e) {

        addMessage(
            "bot",
            "الخادم أرسل ردًا غير صحيح:\n" +
            raw
        );

        statusText.textContent =
            "حدث خطأ في الخادم.";

        return;
    }


    if (data.error) {

        addMessage(
            "bot",
            "خطأ:\n" +
            data.error
        );

        statusText.textContent =
            "حدث خطأ.";

        return;
    }


    if (data.reply) {

        addMessage(
            "bot",
            data.reply
        );

        statusText.textContent =
            "جاهز لتلقي رسالتك التالية.";

        speak(data.reply);

    } else {

        addMessage(
            "bot",
            "لم يصل رد من الذكاء الاصطناعي."
        );
    }


} catch (error) {

    console.error(error);


    thinking.remove();


    addMessage(
        "bot",
        "تعذر الاتصال بملف ro.php.\n\n" +
        error.message
    );


    statusText.textContent =
        "تعذر الاتصال بالخادم.";
}

}

// =====================================================
// الكتابة
// =====================================================

sendBtn.addEventListener(
"click",
function () {

    const text =
        textInput.value.trim();

    if (!text) {
        return;
    }

    textInput.value = "";

    sendText(text);
}

);

textInput.addEventListener(
"keydown",
function (event) {

    if (event.key === "Enter") {

        event.preventDefault();

        sendBtn.click();
    }
}

);

// =====================================================
// 🎤 الميكروفون
// =====================================================

const SpeechRecognition =
window.SpeechRecognition ||
window.webkitSpeechRecognition;

let recognition = null;

let isListening = false;

if (SpeechRecognition) {

recognition =
    new SpeechRecognition();


recognition.lang =
    "ar-SA";


recognition.continuous =
    false;


recognition.interimResults =
    false;


recognition.maxAlternatives =
    1;


recognition.onstart =
    function () {

        isListening = true;

        micBtn.classList.add(
            "recording"
        );

        micIcon.textContent =
            "⏹️";

        statusText.textContent =
            "جاري الاستماع... تحدث الآن 🎤";
    };


recognition.onresult =
    function (event) {

        const text =
            event.results[0][0]
                .transcript
                .trim();


        if (text) {

            sendText(text);
        }
    };


recognition.onerror =
    function (event) {

        console.error(
            "Speech error:",
            event.error
        );


        if (
            event.error ===
            "not-allowed"
        ) {

            addMessage(
                "bot",
                "لم يتم السماح للموقع باستخدام الميكروفون. اسمحي بالوصول إلى الميكروفون من إعدادات المتصفح."
            );

        } else if (
            event.error ===
            "no-speech"
        ) {

            statusText.textContent =
                "لم أسمع صوتًا، حاولي مرة أخرى.";

        } else {

            addMessage(
                "bot",
                "حدث خطأ أثناء استخدام الميكروفون."
            );
        }
    };


recognition.onend =
    function () {

        isListening = false;

        micBtn.classList.remove(
            "recording"
        );

        micIcon.textContent =
            "🎤";

        statusText.textContent =
            "جاهز لتلقي صوتك أو ملفك أو كتاباتك";
    };

}

// =====================================================
// زر الميكروفون
// =====================================================

micBtn.addEventListener(
"click",
function () {

    if (!recognition) {

        addMessage(
            "bot",
            "المتصفح لا يدعم التعرف على الصوت. استخدمي Chrome أو متصفحًا يدعم Speech Recognition."
        );

        return;
    }


    if (isListening) {

        recognition.stop();

        return;
    }


    try {

        recognition.start();

    } catch (error) {

        console.error(error);
    }
}

);

// =====================================================
// 📁 رفع ملف صوتي
// =====================================================

audioFileInput.addEventListener(
"change",
async function (event) {

    const file =
        event.target.files[0];


    if (!file) {
        return;
    }


    console.log(
        "Audio file:",
        file.name
    );


    addMessage(
        "user",
        "📁 تم إرفاق: " +
        file.name
    );


    const thinking =
        addMessage(
            "bot",
            "🎧 جاري تحويل الصوت إلى نص...",
            { thinking: true }
        );


    statusText.textContent =
        "جاري معالجة الملف الصوتي...";


    try {

        const formData =
            new FormData();


        formData.append(
            "audio",
            file
        );


        const response =
            await fetch(
                BACKEND_URL,
                {
                    method: "POST",
                    body: formData
                }
            );


        const raw =
            await response.text();


        console.log(
            "AUDIO RESPONSE:",
            raw
        );


        thinking.remove();


        let data;


        try {

            data =
                JSON.parse(raw);

        } catch (e) {

            addMessage(
                "bot",
                "الخادم أرسل ردًا غير صحيح:\n" +
                raw
            );

            statusText.textContent =
                "حدث خطأ في الخادم.";

            return;
        }


        if (data.error) {

            addMessage(
                "bot",
                "خطأ في الملف الصوتي:\n" +
                data.error
            );

            statusText.textContent =
                "تعذر معالجة الصوت.";

            return;
        }


        // النص الذي فهمه Whisper

        if (data.transcription) {

            addMessage(
                "bot",
                "📝 النص المستخرج:\n" +
                data.transcription
            );
        }


        // رد الذكاء الاصطناعي

        if (data.reply) {

            addMessage(
                "bot",
                data.reply
            );

            speak(data.reply);

        } else {

            addMessage(
                "bot",
                "تم تحويل الصوت ولكن لم يصل رد من الذكاء الاصطناعي."
            );
        }


        statusText.textContent =
            "جاهز لتلقي صوتك أو ملفك أو كتاباتك.";


    } catch (error) {

        console.error(
            "Audio error:",
            error
        );


        thinking.remove();


        addMessage(
            "bot",
            "تعذر رفع أو معالجة الملف الصوتي:\n" +
            error.message
        );


        statusText.textContent =
            "حدث خطأ أثناء معالجة الصوت.";

    } finally {

        audioFileInput.value = "";
    }
}

);
