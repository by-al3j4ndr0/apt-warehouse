import { SignJWT, jwtVerify } from "jose";
import { cookies } from "next/headers";

const COOKIE = "apt_session";
const secret = () => new TextEncoder().encode(process.env.AUTH_SECRET || "development-only-change-me");

export type Session = { username: string; firstName: string; lastName: string };

export async function createSession(session: Session) {
  const token = await new SignJWT(session)
    .setProtectedHeader({ alg: "HS256" })
    .setIssuedAt()
    .setExpirationTime("8h")
    .sign(secret());
  const jar = await cookies();
  jar.set(COOKIE, token, { httpOnly: true, sameSite: "lax", secure: process.env.NODE_ENV === "production", path: "/", maxAge: 60 * 60 * 8 });
}

export async function getSession(): Promise<Session | null> {
  const token = (await cookies()).get(COOKIE)?.value;
  if (!token) return null;
  try {
    const { payload } = await jwtVerify(token, secret());
    if (typeof payload.username !== "string" || typeof payload.firstName !== "string" || typeof payload.lastName !== "string") return null;
    return { username: payload.username, firstName: payload.firstName, lastName: payload.lastName };
  } catch {
    return null;
  }
}

export async function clearSession() {
  (await cookies()).set(COOKIE, "", { httpOnly: true, expires: new Date(0), path: "/" });
}

export function verifyLegacyPassword(password: string, encoded: string) {
  const parts = encoded.split("$");
  if (parts.length < 4) return false;
  const iterations = Number(parts[1]);
  const salt = parts[2];
  const expected = parts[3];
  if (!Number.isFinite(iterations) || iterations <= 0 || !salt || !expected) return false;
  const derived = Buffer.from(require("crypto").pbkdf2Sync(password, salt, iterations, 32, "sha256")).toString("base64");
  return require("crypto").timingSafeEqual(Buffer.from(derived), Buffer.from(expected));
}
