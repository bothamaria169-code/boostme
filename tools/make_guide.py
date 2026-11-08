"""BoostMe user guide as a chat conversation. Run: python tools/make_guide.py BoostMe-Guide.pdf (needs reportlab)."""
import sys
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.colors import HexColor
from reportlab.lib.utils import simpleSplit

pdfmetrics.registerFont(TTFont("UI", "C:/Windows/Fonts/segoeui.ttf"))
pdfmetrics.registerFont(TTFont("UIB", "C:/Windows/Fonts/segoeuib.ttf"))

OUT = sys.argv[1]
W, H = A4
PINK, PLUM, INK, MUTED = HexColor("#c2185b"), HexColor("#2a1036"), HexColor("#1d1b22"), HexColor("#6b6878")
BOT_BG, ME_BG = HexColor("#f3eff6"), HexColor("#c2185b")
M = 40
MAXW = W * 0.68

# (who, text) — "me" = the user asking, "bot" = BoostMe answering. "##" = section chip.
CHAT = [
    ("##", "Getting started"),
    ("me", "Hi! What is BoostMe?"),
    ("bot", "I post your photos and videos to Facebook, Instagram and TikTok at the times you choose, and email you a summary every Monday."),
    ("me", "How do I log in?"),
    ("bot", "Go to boostme.swiftfusion.co.za and use the email and password your account manager sent you. Change the password under My account."),
    ("##", "Your accounts"),
    ("me", "Do I need to connect Facebook, Instagram or TikTok?"),
    ("bot", "No. Your account manager connects and looks after them for you. Your Dashboard shows each one as Connected."),
    ("me", "One says 'Being set up'?"),
    ("bot", "We're still linking it. If it stays that way for more than a day, let your account manager know."),
    ("me", "I changed my Facebook or TikTok password."),
    ("bot", "Tell your account manager so they can reconnect it. Otherwise posts may stop going out."),
    ("##", "Posting"),
    ("me", "How do I add a post?"),
    ("bot", "Press New post.\n1. Add up to 10 photos or one video.\n2. Write a caption.\n3. Tick Facebook, Instagram and/or TikTok.\n4. Pick the date and time.\n5. Press Send for approval."),
    ("me", "Why does it say 'awaiting approval'?"),
    ("bot", "Your account manager checks every post before it goes out. Once approved it shows as Scheduled and posts by itself at the time you chose."),
    ("me", "Do I need to add hashtags?"),
    ("bot", "No. Your salon's hashtags and booking line are added automatically when the caption has none. Add your own if you prefer."),
    ("me", "What works best for hair and nails?"),
    ("bot", "Vertical videos (9:16), 15-60 seconds: before-and-after, braiding time-lapses, nail close-ups. Post 3-5 times a week, ideally 07:00, 12:30 or 18:30."),
    ("##", "TikTok inbox"),
    ("me", "I got a TikTok notification instead of a post?"),
    ("bot", "Normal! TikTok sends our posts to your TikTok inbox first. Open the notification, add a trending sound, and tap Post. Takes 10 seconds."),
    ("##", "Your dashboard & reports"),
    ("me", "How do I see what was posted?"),
    ("bot", "The Dashboard shows posts this week, what's scheduled, and anything that needs attention. Posts lists everything; tap a platform icon to open the live post."),
    ("me", "What's in the Monday email?"),
    ("bot", "How many posts went out per platform, a list with links, and what's coming up next week."),
    ("me", "A post shows 'failed'?"),
    ("bot", "Usually an account needs reconnecting. Your account manager fixes it and presses Retry. We never re-post automatically, so you won't get duplicates."),
    ("me", "Who do I contact?"),
    ("bot", "Your account manager at Swift Fusion: info@swiftfusion.co.za"),
]


def header(c, first):
    c.setFillColor(PLUM)
    c.rect(0, H - (120 if first else 50), W, 120 if first else 50, stroke=0, fill=1)
    c.setFillColor(HexColor("#ffffff"))
    if first:
        c.setFont("UIB", 26)
        c.drawString(M, H - 58, "BoostMe")
        c.setFillColor(HexColor("#f48fb1"))
        c.setFont("UI", 13)
        c.drawString(M, H - 82, "User guide - just ask")
        c.setFillColor(HexColor("#ffffff"))
        c.setFont("UI", 10)
        c.drawString(M, H - 102, "boostme.swiftfusion.co.za")
        c.circle(W - M - 8, H - 60, 5, stroke=0, fill=1)
        c.setFont("UI", 9)
        c.drawRightString(W - M - 18, H - 63, "online")
        return H - 150
    c.setFont("UIB", 14)
    c.drawString(M, H - 32, "BoostMe")
    c.setFont("UI", 9)
    c.drawRightString(W - M, H - 31, "User guide")
    return H - 80


def footer(c, n):
    c.setFont("UI", 8)
    c.setFillColor(MUTED)
    c.drawCentredString(W / 2, 22, f"BoostMe by Swift Fusion  |  page {n}")


def bubble_lines(text, font, size, width):
    out = []
    for para in text.split("\n"):
        out += simpleSplit(para, font, size, width) or [""]
    return out


c = canvas.Canvas(OUT, pagesize=A4)
c.setTitle("BoostMe User Guide")
page = 1
y = header(c, True)
for who, text in CHAT:
    if who == "##":
        need = 40
    else:
        lines = bubble_lines(text, "UI", 10.5, MAXW - 24)
        need = len(lines) * 15 + 26
    if y - need < 50:
        footer(c, page)
        c.showPage()
        page += 1
        y = header(c, False)
    if who == "##":
        y -= 20
        tw = pdfmetrics.stringWidth(text.upper(), "UIB", 8.5) + 20
        c.setFillColor(HexColor("#fde4ee"))
        c.roundRect((W - tw) / 2, y - 6, tw, 18, 9, stroke=0, fill=1)
        c.setFillColor(PINK)
        c.setFont("UIB", 8.5)
        c.drawCentredString(W / 2, y, text.upper())
        y -= 24
        continue
    width = max(pdfmetrics.stringWidth(l, "UI", 10.5) for l in lines) + 24
    h = len(lines) * 15 + 12
    if who == "me":
        x = W - M - width
        c.setFillColor(ME_BG)
        c.roundRect(x, y - h, width, h, 10, stroke=0, fill=1)
        c.setFillColor(HexColor("#ffffff"))
    else:
        c.setFillColor(PINK)
        c.circle(M + 11, y - 11, 11, stroke=0, fill=1)
        c.setFillColor(HexColor("#ffffff"))
        c.setFont("UIB", 9)
        c.drawCentredString(M + 11, y - 14.5, "B")
        x = M + 30
        c.setFillColor(BOT_BG)
        c.roundRect(x, y - h, width, h, 10, stroke=0, fill=1)
        c.setFillColor(INK)
    c.setFont("UI", 10.5)
    ty = y - 18
    for line in lines:
        c.drawString(x + 12, ty, line)
        ty -= 15
    y -= h + 12
footer(c, page)
c.save()
print("pages:", page)
