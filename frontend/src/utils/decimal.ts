const SCALE = 8n;
const MULTIPLIER = 10n ** SCALE;

export function parseDecimalToAtomic(value: string): bigint | null {
  const normalized = value.trim();
  if (!normalized) return null;
  if (!/^\d+(\.\d+)?$/.test(normalized)) return null;

  const [intPart, fracPart = ""] = normalized.split(".");
  const frac = (fracPart + "0".repeat(Number(SCALE))).slice(0, Number(SCALE));

  return BigInt(intPart) * MULTIPLIER + BigInt(frac);
}

export function formatAtomicToDecimal(value: bigint): string {
  const sign = value < 0n ? "-" : "";
  const abs = value < 0n ? -value : value;

  const intPart = abs / MULTIPLIER;
  const fracPart = abs % MULTIPLIER;

  return `${sign}${intPart}.${fracPart.toString().padStart(Number(SCALE), "0")}`;
}

export function mulDecimalStrings(left: string, right: string): string | null {
  const leftAtomic = parseDecimalToAtomic(left);
  const rightAtomic = parseDecimalToAtomic(right);
  if (leftAtomic === null || rightAtomic === null) return null;

  const productScaled16 = leftAtomic * rightAtomic;
  const rounded = (productScaled16 + MULTIPLIER / 2n) / MULTIPLIER;

  return formatAtomicToDecimal(rounded);
}

export function mulDecimalStringByRatio(
  value: string,
  numerator: bigint,
  denominator: bigint
): string | null {
  const atomic = parseDecimalToAtomic(value);
  if (atomic === null) return null;

  const rounded = (atomic * numerator + denominator / 2n) / denominator;
  return formatAtomicToDecimal(rounded);
}

