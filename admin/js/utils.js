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

export function update_character_count(e)
{
  const cur_length = $(this).val().length;
  const max_length = $(this).attr('maxlength');
  const cc = $(this).parent().children('.char-count');

  cc.children('.cur').text(cur_length);

  if(cur_length > 0.9*max_length) {
    cc.addClass('danger').removeClass('warning');
  } else if(cur_length > 0.75*max_length) {
    cc.addClass('warning').removeClass('danger');
  } else {
    cc.removeClass('warning danger');
  }
}

export function validate_markdown(s)
{
  const regex = /<\s*([a-z][a-z0-9-]*)\b[^>]*>/gi;
  const allowed_tags = ["b","strong","em","i","code","br","pre"];

  let match;
  while ((match = regex.exec(s)) !== null) {
    const tag = match[1].toLowerCase();
    if (!allowed_tags.includes(tag)) {
      return `<${tag}> not allowed`;
    }
  }

  return undefined;
}
