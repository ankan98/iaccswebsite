import FooterClient from "./footer-client";

async function getSettings() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_settings.php`);
    if (!res.ok) return null;
    return await res.json();
  } catch (e) {
    console.error("Failed to load footer settings:", e);
    return null;
  }
}

async function getFooterMenu() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_menus.php?menu_id=2`);
    if (!res.ok) return [];
    const data = await res.json();
    return data.items || [];
  } catch (e) {
    console.error("Failed to load footer menu:", e);
    return [];
  }
}

export default async function Footer() {
  const settings = await getSettings();
  const footerMenuItems = await getFooterMenu();

  return (
    <FooterClient
      initialSettings={settings}
      initialFooterMenuItems={footerMenuItems}
    />
  );
}