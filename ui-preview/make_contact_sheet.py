from pathlib import Path
from PIL import Image, ImageDraw

root = Path(__file__).parent / "screenshots"
files = [root / name for name in [
    "home.png", "shop.png", "product.png", "cart.png", "checkout.png",
    "home-mobile.png", "shop-mobile.png", "product-mobile.png",
]]
thumbs = []
for path in files:
    image = Image.open(path).convert("RGB")
    image.thumbnail((520, 360))
    canvas = Image.new("RGB", (540, 400), "white")
    x = (540 - image.width) // 2
    canvas.paste(image, (x, 28))
    draw = ImageDraw.Draw(canvas)
    draw.text((12, 8), path.name, fill="black")
    thumbs.append(canvas)
contact = Image.new("RGB", (1080, 1600), "#e7e7e7")
for index, thumb in enumerate(thumbs):
    contact.paste(thumb, ((index % 2) * 540, (index // 2) * 400))
contact.save(root / "contact-sheet.png", optimize=True)
print(root / "contact-sheet.png")
