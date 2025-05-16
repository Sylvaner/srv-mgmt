export function getLastAction(
  lastUpdate?: string,
  lastCheck?: string,
): string | null {
  if (lastUpdate === undefined) {
    if (lastCheck === undefined) {
      return null;
    } else {
      return lastCheck;
    }
  }
  if (lastCheck === undefined) {
    return lastUpdate;
  } else {
    const updateDate = new Date(Date.parse(lastUpdate));
    const checkDate = new Date(Date.parse(lastCheck));
    return updateDate > checkDate ? lastUpdate : lastCheck;
  }
}

export function showLastAction(
  lastUpdate?: string,
  lastCheck?: string,
): string {
  const lastAction = getLastAction(lastUpdate, lastCheck);
  if (lastAction === null) {
    return '';
  } else {
    return showDate(lastAction);
  }
}

export function showDate(dateToShow: string, withTime: boolean = true): string {
  const d = new Date(Date.parse(dateToShow));
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const userLang = navigator.language || (navigator as any).userLanguage;
  const options: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'numeric',
    day: 'numeric',
  };
  if (withTime) {
    options.hour = '2-digit';
    options.minute = '2-digit';
  }
  return d.toLocaleDateString(userLang, options);
}

export function dateToSql(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');

  return `${year}-${month}-${day} ${hours}:${minutes}`;
}
