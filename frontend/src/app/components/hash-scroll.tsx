"use client";

import { useEffect } from "react";

export default function HashScroll(props: { offset?: number }) {
  useEffect(() => {
    const hash = window.location.hash;
    if (!hash) return;
    const id = hash.replace("#", "");
    if (!id) return;

    const el = document.getElementById(id);
    if (!el) return;

    requestAnimationFrame(() => {
      el.scrollIntoView({ behavior: "smooth", block: "start" });
      if (props.offset) {
        window.scrollBy(0, -props.offset);
      }
    });
  }, [props.offset]);

  return null;
}
