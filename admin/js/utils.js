export function deepCopy(obj)
{
  if( (obj === null) || (typeof obj !== 'object') ) {
    return obj;
  }
  if( Array.isArray(obj) ) {
    return obj.map(deepCopy);
  }
  const result = {};
  for(const key of Object.keys(obj)) {
    result[key] = deepCopy(obj[key]);
  }
  return result;
}

