import sys
import os

if len(sys.argv) < 3:
    print("Usage: python pdf_to_image.py <pdf_path> <output_jpg_path> [scale]")
    sys.exit(1)

pdf_path = sys.argv[1]
out_path = sys.argv[2]
scale = float(sys.argv[3]) if len(sys.argv) > 3 else 2.0

try:
    import pypdfium2 as pdfium
    from PIL import Image
    if not os.path.exists(pdf_path):
        print(f"File not found: {pdf_path}", file=sys.stderr)
        sys.exit(1)
        
    doc = pdfium.PdfDocument(pdf_path)
    if len(doc) == 0:
        print("PDF has no pages", file=sys.stderr)
        sys.exit(1)
        
    page = doc[0]
    img = page.render(scale=scale).to_pil()  # type: ignore[arg-type]
    
    # Ensure output dir exists
    out_dir = os.path.dirname(out_path)
    if out_dir and not os.path.exists(out_dir):
        os.makedirs(out_dir, exist_ok=True)
        
    # Convert RGBA to RGB if needed
    if img.mode in ('RGBA', 'LA') or (img.mode == 'P' and 'transparency' in img.info):
        bg = Image.new('RGB', img.size, (255, 255, 255))
        bg.paste(img, mask=img.split()[-1] if img.mode in ('RGBA', 'LA') else None)
        img = bg
    elif img.mode != 'RGB':
        img = img.convert('RGB')
        
    img.save(out_path, 'JPEG', quality=90)
    print("SUCCESS")
except Exception as e:
    print(f"ERROR: {e}", file=sys.stderr)
    sys.exit(1)
