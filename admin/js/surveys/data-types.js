/**
 * @typedef {"section"|"group"} ContainerType
 */

/**
 * @typedef {"group"|"question"} ContentType
 */

/**
 * @typedef {"section"|"group"|"question"} ItemType
 */

/**
 * @typedef {Object} QuestionID
 * @property {number} question_id
 */

/**
 * @typedef {"INFO"|"BOOL"|"FREETEXT"|"SELECT_ONE"|"SELECT_MULTI"} QuestionType
 */

/**
 * @typedef {[jQuery<HTMLLIElement>,jQuery<HTMLULElement>]} jQueryTreeNodePair
 */

/**
 * @typedef {[HTMLLIElement,HTMLULElement]} DOMTreeNodePair
 */

/**
 * @typedef {Object} SelectedItem
 * @property {ItemType} item_type
 * @property {number} item_id
 */

/**
 * @typedef {WhereInSection|WhereRelativeToContent} WhereToAddGroup
 */

/**
 * @typedef {WhereInSection|WhereInGroup|WhereRelativeToContent} WhereToAddQuestion
 */

/**
 * @typedef {Object} WhereInSection
 * @property {number} section_id ID of the section into which to add the item
 * @property {booleanY} [at_end=false] If item should be added to bottom of the section
 */

/**
 * @typedef {Object} WhereInGroup
 * @property {number} group_id ID of the group into which to add the item
 * @property {booleanY} [at_end=false] If item should be added to bottom of the group
 */

/**
 * @typedef {Object} WhereRelativeToSection
 * @property {number} section_id ID of the existing section used to position new section
 * @property {-1|1} offset Where to put the new section: -1=before, 1=after relative to existing section
 */

/**
 * @typedef {Object} WhereRelativeToContent
 * @property {number} ref_id ID of existing item used to position new group
 * @property {ContentType} ref_type type of the reference item
 * @property {-1|1} offset Where to put the new item: -1=before, 1=after
 */

/**
 * @typedef {Object} GroupStructure
 * @property {number} group_id
 * @property {Array<number>} content array of question IDs
 */

/**
 * @typedef {Array<QuestionID|GroupStructure>} SurveyStructure
 */

