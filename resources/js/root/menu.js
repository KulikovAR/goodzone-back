import style from "../components/Header/Header.module.scss";

export const menu1 = [
  {
    container: style.link1,
    type: "Link",
    url: "/",
    text: "Авиабилеты и отели для визы",
  },
  { container: style.link1, type: "Link", url: "/faq", text: "Faq" },
  { container: style.link1, type: "Link", url: "/reviews", text: "Отзывы" },
  { container: style.link1, type: "Link", url: "/products", text: "Продукты" },
  { container: style.link1, type: "Link", url: "/help", text: "Помощь" },
];

export const menu2 = [
  {
    container: style.link2,
    type: "Link",
    url: "/help",
    text: "Помощь",
  },
  { container: style.link2, type: "Link", url: "/about", text: "О сервисе" },
  { container: style.link2, type: "Link", url: "/contacts", text: "Контакты" },
];