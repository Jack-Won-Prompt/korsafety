# 배경 화이트 정규화로 "YES WILL" 워터마크 제거.
# 테두리에서 연결된 흰 배경만 순백으로 만들고, 배경 안의 큰 밝은 덩어리(제품)는
# 보호해 손상시키지 않는다. photo/gallery/detail 의 JPG/PNG 를 제자리에서 갱신.
import os, sys, glob
import numpy as np
from PIL import Image
from scipy import ndimage

ROOT = os.path.join(os.path.dirname(__file__), '..', 'public', 'shop', 'img')

def process(arr, near=205, whiteish=236):
    h, w = arr.shape[:2]
    maxblob = max(3000, int(0.02 * h * w))   # 이보다 큰 밝은 덩어리는 제품 -> 보호
    im = arr.astype(np.int16)
    mn = im.min(2)
    nearwhite = mn >= near
    lbl, _ = ndimage.label(nearwhite)
    border = set(lbl[0, :]) | set(lbl[-1, :]) | set(lbl[:, 0]) | set(lbl[:, -1])
    border.discard(0)
    if not border:
        return arr, 0
    bg = np.isin(lbl, list(border))
    nw = bg & (mn < whiteish)
    l2, n2 = ndimage.label(nw)
    protect = np.zeros_like(bg)
    if n2 > 0:
        sizes = np.bincount(l2.ravel())
        big = [i for i in range(1, n2 + 1) if sizes[i] > maxblob]
        if big:
            protect = np.isin(l2, big)
    whiten = bg & (~protect)
    changed = int(whiten.sum())
    if changed == 0:
        return arr, 0
    out = arr.copy()
    out[whiten] = [255, 255, 255]
    return out, changed

def main():
    files = []
    for ext in ('jpg', 'jpeg', 'png'):
        files += glob.glob(os.path.join(ROOT, '*', f'*.{ext}'))
    total = len(files)
    done = touched = errors = 0
    for f in files:
        done += 1
        try:
            im = Image.open(f)
            fmt = im.format
            arr = np.asarray(im.convert('RGB'))
            out, changed = process(arr)
            if changed > 20:  # 의미있는 변화가 있을 때만 저장
                save = Image.fromarray(out)
                if (fmt or '').upper() in ('JPEG', 'JPG') or f.lower().endswith(('.jpg', '.jpeg')):
                    save.save(f, 'JPEG', quality=90)
                else:
                    save.save(f, 'PNG')
                touched += 1
        except Exception as e:
            errors += 1
        if done % 200 == 0 or done == total:
            sys.stdout.write(f"\r  {done}/{total}  갱신={touched} 오류={errors}   ")
            sys.stdout.flush()
    print(f"\nDONE. 처리 {total} / 워터마크·배경 정규화 {touched} / 오류 {errors}")

if __name__ == '__main__':
    main()
