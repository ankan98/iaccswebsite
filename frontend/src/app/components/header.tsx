import HeaderClient from "./header-client";

async function getSettings() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_settings.php`);
    if (!res.ok) return null;
    return await res.json();
  } catch (e) {
    console.error("Failed to load settings:", e);
    return null;
  }
}

async function getHeaderMenu() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_menus.php?menu_id=1`);
    if (!res.ok) return [];
    const data = await res.json();
    return data.items || [];
  } catch (e) {
    console.error("Failed to load menu:", e);
    return [];
  }
}

export default async function Header() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  const settings = await getSettings();
  const menuItems = await getHeaderMenu();

  return <HeaderClient settings={settings} menuItems={menuItems} backendUrl={backendUrl} />;
}

